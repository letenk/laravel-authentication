<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['email', 'phone']);
            $table->enum('purpose', ['email_verification', 'phone_verification', 'password_reset']);
            $table->string('code', 10);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->unsignedSmallInteger('submit_attempt')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'channel', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
