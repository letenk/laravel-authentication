# Laravel Auth System — Project Conventions

## Architecture
Controller → Service → Repository. Tidak ada interface untuk Repository.
Semua response API menggunakan format konsisten: `{status, message, data}`.

## Repository Pattern

Setiap repository wajib menggunakan tiga komponen:

### 1. Custom Query Builder (Scope)
Extends `Illuminate\Database\Eloquent\Builder`. Berisi filter methods yang composable.

```php
// app/Models/Scope/UserScope.php
class UserScope extends Builder
{
    public function filterByEmail(string $email): static
    {
        return $this->where('email', $email);
    }
}
```

Model harus register scope-nya via `newEloquentBuilder()` DAN override `query()` untuk IDE type hint:
```php
// Untuk IDE/static analysis — tanpa ini autocomplete filter methods tidak muncul
public static function query(): UserScope
{
    return parent::query();
}

// Untuk runtime — ini yang benar-benar mengubah builder yang dipakai Laravel
public function newEloquentBuilder($query): UserScope
{
    return new UserScope($query);
}
```

Keduanya wajib ada. `query()` hanya untuk type hint, `newEloquentBuilder()` yang bekerja di runtime.

### 2. Repository DTO
Digunakan sebagai parameter untuk `first()` dan `get()`. Selalu punya tiga field: `select`, `filters`, `eagerLoadRelation`. Tidak pakai `orderBy` kecuali method memang butuh ordering (e.g. `getListWithPaginate`).

```php
// app/DTOs/User/UserRepositoryDTO.php
class UserRepositoryDTO
{
    public array $select;
    public array $filters;
    public array $eagerLoadRelation;

    public function __construct(array $params)
    {
        $this->select            = $params['select'] ?? ['*'];
        $this->filters           = $params['filters'] ?? [];
        $this->eagerLoadRelation = $params['eagerLoadRelation'] ?? [];
    }
}
```

### 3. Repository Class
Selalu punya `baseQuery()` private, `applyFilters()` public, `first()`, `get()`, dan method domain-specific seperti `create()`, `save()`.

```php
class UserRepository
{
    private function baseQuery(array $select = ['*'], array $relations = []): UserScope
    {
        $query = User::query()->select($select);
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query;
    }

    public function applyFilters(UserScope $query, array $filters): UserScope
    {
        if (isset($filters['id'])) {
            $query->filterById($filters['id']);
        }

        if (isset($filters['email'])) {
            $query->filterByEmail($filters['email']);
        }

        if (isset($filters['phone'])) {
            $query->filterByPhone($filters['phone']);
        }

        return $query;
    }

    // Selalu step-by-step, jangan nested — lebih mudah debug dan dibaca
    public function first(UserRepositoryDTO $dto): ?User
    {
        $query = $this->baseQuery($dto->select, $dto->eagerLoadRelation);
        $query = $this->applyFilters($query, $dto->filters);

        return $query->first();
    }

    public function get(UserRepositoryDTO $dto): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->baseQuery($dto->select, $dto->eagerLoadRelation);
        $query = $this->applyFilters($query, $dto->filters);

        return $query->get();
    }
}
```

### Pemanggilan dari Service
```php
$user = $this->userRepository->first(new UserRepositoryDTO([
    'filters' => ['email' => 'a@b.com'],
]));
```


## Code Style

- Selalu gunakan block `if` dengan kurung kurawal — jangan single-line `if` tanpa kurung
- Untuk query building di repository, gunakan step-by-step (assign ke variabel dulu) bukan nested/chained — lebih mudah debug

```php
// ✅ Benar
$query = $this->baseQuery($dto->select, $dto->eagerLoadRelation);
$query = $this->applyFilters($query, $dto->filters);
return $query->first();

// ❌ Jangan
return $this->applyFilters($this->baseQuery(...), $dto->filters)->first();
```

## Dependency Injection

Aturan ini berlaku untuk **Service, Repository, dan Controller** — class yang menerima dependency (bukan data).

**✅ Wajib: deklarasi eksplisit + manual assignment**
```php
class AuthService
{
    protected UserRepository $userRepository;
    protected OtpService $otpService;

    public function __construct(
        UserRepository $userRepository,
        OtpService $otpService,
    ) {
        $this->userRepository = $userRepository;
        $this->otpService     = $otpService;
    }
}
```

**❌ Jangan gunakan promoted properties untuk DI:**
```php
public function __construct(
    private UserRepository $userRepository,
) {}
```

## Request / Data Objects (spatie/laravel-data)

Untuk request validation dan data transfer, gunakan `spatie/laravel-data`. **Di sini promoted + readonly properties adalah idiomatis dan diizinkan** karena Data object adalah value object, bukan class dengan dependency.

```php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;

class RegisterRequest extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
```

Inject ke controller via `MyRequest::from($request)` atau type-hint langsung (spatie handles it automatically via Laravel's container).

## Password Validation

Selalu gunakan `Password::defaults()` untuk field password. Jangan hardcode rule password di masing-masing request.

```php
'password' => ['required', 'string', Password::defaults()],
```

Policy didefinisikan di `app/Support/BootValidator.php`, dipanggil dari `AppServiceProvider::boot()`. Kalau ada custom validation rule baru, tambahkan method baru di `BootValidator` — jangan langsung di `AppServiceProvider`.

## Logging

### Kapan log dan di mana

| Jenis Error | Di mana log | Level | Keterangan |
|---|---|---|---|
| Unexpected system error | Global handler (`bootstrap/app.php`) | `Log::error` | Otomatis — sudah terpasang |
| Critical domain operation gagal di service | Service layer | `Log::error` | Harus eksplisit karena global handler tidak punya konteks domain |
| Failed login / auth attempt | Service layer | `Log::warning` | Security observability, bukan error |
| Expected domain error (wrong password, not found, dll) | **Tidak perlu di-log** | — | Ini `GeneralException`, bukan bug |

### Contoh logging di service layer

Gunakan `Log::error` di service layer **hanya** untuk operasi kritis yang butuh konteks domain (bukan untuk domain error yang diharapkan):

```php
// ✅ Log ini — operasi kritis yang tidak terduga gagal
public function sendOtpEmail(User $user): void
{
    try {
        Mail::to($user->email)->queue(new OtpMail($user));
    } catch (\Throwable $e) {
        Log::error('Failed to queue OTP email', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'error'   => $e->getMessage(),
        ]);
        throw GeneralException::create('Failed to send OTP. Please try again.', null, 500);
    }
}

// ❌ Jangan log ini — ini domain error yang diharapkan
public function login(LoginRequest $dto): array
{
    if (!$user || !Hash::check($dto->password, $user->password)) {
        // Jangan Log::error di sini — salah password bukan bug
        throw GeneralException::create('Invalid credentials.', null, 401);
    }
}
```

### Context yang wajib disertakan saat Log::error di service

```php
Log::error('Deskripsi singkat apa yang gagal', [
    'user_id'   => $user->id,      // siapa yang terdampak
    'entity_id' => $entity->id,    // entitas apa yang diproses
    'error'     => $e->getMessage(),
]);
```

## Exception Handling
Selalu gunakan `GeneralException::create($message, $data, $errCode)` untuk domain error. Jangan throw `\Exception` langsung dari service/repository.

## Folder Structure Conventions
- `app/Repository/` — repository classes
- `app/Models/Scope/` — custom query builders per model
- `app/DTOs/{Feature}/` — DTO classes (misal `app/DTOs/User/`, `app/DTOs/Auth/`)
- `app/Http/Services/` — service classes
- `app/Http/Controllers/Api/` — API controllers (semua extend `BaseController`)
- `app/Http/Requests/{Feature}/` — request/validation classes
- `app/Http/Requests/Builders/` — `CustomDtoBuilder` dan `CustomRequestBuilder`
