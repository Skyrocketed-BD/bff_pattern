<?php

namespace App\Http\Controllers\Bff;

use App\Http\Controllers\Controller;
use App\Services\ApiClientService;
use Illuminate\Http\JsonResponse;

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
}
