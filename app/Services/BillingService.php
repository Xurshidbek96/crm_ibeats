<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillingService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.billing.base_url', '');
        $this->timeout = config('services.billing.timeout', 30);
    }

    /**
     * Create a new record in billing system.
     */
    public function create(string $model, array $data): ?int
    {
        return $this->sendRequest('POST', "$model/create", $data);
    }

    /**
     * Update an existing record in billing system.
     */
    public function update(string $model, int $billingId, array $data): ?int
    {
        return $this->sendRequest('PUT', "$model/update/$billingId", $data);
    }

    /**
     * Delete a record from billing system.
     */
    public function delete(string $model, int $billingId): ?int
    {
        return $this->sendRequest('DELETE', "$model/delete/$billingId");
    }

    /**
     * Generic method to handle different types of billing operations.
     */
    public function send(string $action, string $model, array $data = []): ?int
    {
        switch ($action) {
            case 'create':
                return $this->create($model, $data);
            case 'update':
                if (!isset($data['billing_id'])) {
                    Log::error('Billing ID is required for update operation', ['data' => $data]);
                    return null;
                }
                return $this->update($model, $data['billing_id'], $data);
            case 'delete':
                if (!isset($data['billing_id'])) {
                    Log::error('Billing ID is required for delete operation', ['data' => $data]);
                    return null;
                }
                return $this->delete($model, $data['billing_id']);
            default:
                Log::error('Invalid billing action', ['action' => $action]);
                return null;
        }
    }

    /**
     * Send HTTP request to billing API.
     */
    private function sendRequest(string $method, string $endpoint, array $data = []): ?int
    {
        try {
            $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

            $response = $this->makeHttpRequest($method, $url, $data);

            if (!$response->successful()) {
                $this->logApiError($response, $method, $endpoint, $data);
                return null;
            }

            return $this->extractIdFromResponse($response);

        } catch (Exception $e) {
            $this->logException($e, $method, $endpoint, $data);
            return null;
        }
    }

    /**
     * Make HTTP request based on method.
     */
    private function makeHttpRequest(string $method, string $url, array $data = []): Response
    {
        $httpClient = Http::timeout($this->timeout);

        switch (strtoupper($method)) {
            case 'POST':
                return $httpClient->post($url, $data);
            case 'PUT':
                return $httpClient->put($url, $data);
            case 'DELETE':
                return $httpClient->delete($url);
            case 'GET':
                return $httpClient->get($url, $data);
            default:
                throw new Exception("Unsupported HTTP method: $method");
        }
    }

    /**
     * Extract ID from API response.
     */
    private function extractIdFromResponse(Response $response): ?int
    {
        $responseData = $response->json();

        if (!isset($responseData['id'])) {
            Log::error('Billing Response Error: ID not found', [
                'response' => $responseData,
                'status' => $response->status()
            ]);
            return null;
        }

        return (int) $responseData['id'];
    }

    /**
     * Log API errors.
     */
    private function logApiError(Response $response, string $method, string $endpoint, array $data): void
    {
        Log::error('Billing API Error', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'body' => $response->body(),
            'data' => $data
        ]);
    }

    /**
     * Log exceptions.
     */
    private function logException(Exception $e, string $method, string $endpoint, array $data): void
    {
        Log::error('Billing Service Exception', [
            'method' => $method,
            'endpoint' => $endpoint,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'data' => $data
        ]);
    }

    /**
     * Test billing service connectivity.
     */
    public function testConnection(): array
    {
        try {
            $response = Http::timeout($this->timeout)->get($this->baseUrl . '/health');

            return [
                'connected' => $response->successful(),
                'status' => $response->status(),
                'response_time' => $response->transferStats?->getTransferTime() ?? 0
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get billing service configuration.
     */
    public function getConfig(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'available' => !empty($this->baseUrl)
        ];
    }
}
