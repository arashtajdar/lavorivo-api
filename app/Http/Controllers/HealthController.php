<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Perform a comprehensive health check of the application.
     *
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        $status = [
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'environment' => $this->checkEnvironment(),
            ]
        ];

        // Determine overall status
        $overallStatus = 'ok';
        foreach ($status['checks'] as $check) {
            if ($check['status'] !== 'ok') {
                $overallStatus = 'error';
                break;
            }
        }

        $status['status'] = $overallStatus;
        
        // Log health check result if there's an issue
        if ($overallStatus !== 'ok') {
            Log::warning('Health check failed', $status);
        }

        return response()->json($status, $overallStatus === 'ok' ? 200 : 503);
    }

    /**
     * Check database connectivity.
     *
     * @return array
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2); // in milliseconds
            
            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'response_time_ms' => $responseTime
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check cache connectivity.
     *
     * @return array
     */
    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            $key = 'health_check_' . uniqid();
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2); // in milliseconds
            
            return [
                'status' => $value === 'ok' ? 'ok' : 'error',
                'message' => $value === 'ok' ? 'Cache is working' : 'Cache returned unexpected value',
                'response_time_ms' => $responseTime
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check storage connectivity.
     *
     * @return array
     */
    private function checkStorage(): array
    {
        try {
            $startTime = microtime(true);
            $testFile = 'health_check_' . uniqid() . '.txt';
            Storage::put($testFile, 'ok');
            $content = Storage::get($testFile);
            Storage::delete($testFile);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2); // in milliseconds
            
            return [
                'status' => $content === 'ok' ? 'ok' : 'error',
                'message' => $content === 'ok' ? 'Storage is working' : 'Storage returned unexpected value',
                'response_time_ms' => $responseTime
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check environment configuration.
     *
     * @return array
     */
    private function checkEnvironment(): array
    {
        $requiredEnvVars = [
            'APP_ENV',
            'APP_KEY',
            'DB_CONNECTION',
            'CACHE_DRIVER',
            'QUEUE_CONNECTION',
        ];
        
        $missingVars = [];
        foreach ($requiredEnvVars as $var) {
            if (empty(config($var))) {
                $missingVars[] = $var;
            }
        }
        
        if (empty($missingVars)) {
            return [
                'status' => 'ok',
                'message' => 'Environment configuration is valid'
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'Missing required environment variables: ' . implode(', ', $missingVars)
        ];
    }
} 