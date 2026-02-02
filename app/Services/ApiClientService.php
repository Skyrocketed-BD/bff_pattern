<?php
// app/Services/Bff/ApiClientService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiClientService
{
    private string $baseUrl = 'http://127.0.0.1:8080/api';
    private int $timeout    = 30;
    private ?string $token  = null;

    public function __construct()
    {
        $this->baseUrl = $this->baseUrl;
        $this->timeout = $this->timeout;
    }

    /**
     * Set access token
     */
    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * GET request with error handling
     */
    public function get(string $endpoint, array $params = [])
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * POST request
     */
    public function post(string $endpoint, array $data = [])
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * PUT request
     */
    public function put(string $endpoint, array $data = [])
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * DELETE request
     */
    public function delete(string $endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Generic request with retry and error handling
     */
    private function request(string $method, string $endpoint, array $options = [])
    {
        $url = $this->baseUrl . $endpoint;

        try {
            $http = Http::timeout($this->timeout)
                ->retry(3, 100);  // Retry 3x dengan delay 100ms

            // Add authorization if token exists
            if ($this->token) {
                $http = $http->withToken($this->token);
            }

            // Add headers
            $http = $http->withHeaders([
                'Accept'        => 'application/json',
                'X-BFF-Request' => 'true',
            ]);

            // Make request
            $response = $http->send($method, $url, $options);

            // Log failed requests
            if ($response->failed()) {
                Log::error('BFF API Request Failed', [
                    'method'   => $method,
                    'url'      => $url,
                    'status'   => $response->status(),
                    'response' => $response->body()
                ]);
            }

            return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('BFF Connection Error', [
                'method' => $method,
                'url'    => $url,
                'error'  => $e->getMessage()
            ]);

            throw new \Exception('Unable to connect to backend service');
        } catch (\Exception $e) {
            Log::error('BFF Request Exception', [
                'method' => $method,
                'url'    => $url,
                'error'  => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
