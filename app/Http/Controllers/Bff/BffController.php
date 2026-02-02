<?php

namespace App\Http\Controllers\Bff;

use App\Http\Controllers\Controller;
use App\Services\ApiClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class BffController extends Controller
{
    protected $apiClientService;

    public function __construct(ApiClientService $apiClientService)
    {
        $this->apiClientService = $apiClientService;
        $this->initializeToken();
    }

    /**
     * Initialize token from session
     */
    protected function initializeToken(): void
    {
        $token = session('access_token');

        if ($token) {
            $this->apiClientService->setToken($token);
        }
    }

    /**
     * Handle API response
     */
    protected function handleApiResponse($response): JsonResponse
    {
        if ($response->successful()) {
            return response()->json($response->json(), $response->status());
        }

        // Handle different error codes
        $status  = $response->status();
        $message = $response->json('message', 'Request failed');

        // Log error
        \Log::warning('BFF API Error', [
            'status'   => $status,
            'message'  => $message,
            'response' => $response->body()
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $response->json('errors', [])
        ], $status);
    }

    protected function handleException(\Exception $e): JsonResponse
    {
        // Handle HTTP Client Exceptions
        if ($e instanceof \Illuminate\Http\Client\RequestException) {
            $body = $e->response?->body();

            if ($body) {
                $data = json_decode($body, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => $data['success'] ?? false,
                        'message' => $data['message'] ?? 'Validation error',
                        'errors'  => $data['errors'] ?? [],
                    ], $e->response->status());
                }
            }
        }

        // Di development, tampilkan detail error
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred',
            'error'   => $e->getMessage(),
            'type'    => get_class($e),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => explode("\n", $e->getTraceAsString())
        ], 500);
    }
}
