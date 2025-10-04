<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponseService
{
    /**
     * Return a success response.
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
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
    public static function error(string $message = 'Error', int $statusCode = 400, $errors = null): JsonResponse
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
    public static function paginated(LengthAwarePaginator $paginator, string $message = 'Data retrieved successfully'): JsonResponse
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
    public static function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }

    /**
     * Return a not found response.
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Return an unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Return a forbidden response.
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Return a server error response.
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Return a created response.
     */
    public static function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Return a no content response.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a custom response with additional metadata.
     */
    public static function withMeta($data, array $meta, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ], $statusCode);
    }

    /**
     * Return a response for bulk operations.
     */
    public static function bulk(array $results, string $message = 'Bulk operation completed'): JsonResponse
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