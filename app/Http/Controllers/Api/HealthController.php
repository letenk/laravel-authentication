<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends BaseController
{
    public function index(): JsonResponse
    {
        $data = [
            'db'    => $this->checkDb(),
            'cache' => $this->checkCache(),
        ];

        $isDown = in_array('down', $data);

        return $isDown
            ? $this->errorResponse('Service degraded.', $data, 500)
            : $this->successResponse('OK', $data);
    }

    private function checkDb(): string
    {
        try {
            DB::connection()->getPdo();
            return 'up';
        } catch (\Throwable) {
            return 'down';
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::set('health_ping', 1, 5);
            return 'up';
        } catch (\Throwable) {
            return 'down';
        }
    }
}
