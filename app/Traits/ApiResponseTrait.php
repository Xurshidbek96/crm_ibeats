<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    /**
     * Return a success response.
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message = 'Error', int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'status' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated response.
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages()
            ]
        ]);
    }

    /**
     * Return a validation error response.
     */
    protected function validationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }

    /**
     * Return a not found response.
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return a forbidden response.
     */
    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Return a server error response.
     */
    protected function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, 500);
    }

    /**
     * Return a created response.
     */
    protected function createdResponse($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a response with custom format (for legacy compatibility).
     */
    protected function legacyResponse(bool $status, $data = null, string $message = ''): JsonResponse
    {
        $response = ['status' => $status];
        
        if (!empty($message)) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    /**
     * Return a response for bulk operations.
     */
    protected function bulkResponse(array $results, string $message = 'Bulk operation completed'): JsonResponse
    {
        $successful = collect($results)->where('success', true)->count();
        $failed = collect($results)->where('success', false)->count();

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $results,
            'summary' => [
                'total' => count($results),
                'successful' => $successful,
                'failed' => $failed
            ]
        ]);
    }
}