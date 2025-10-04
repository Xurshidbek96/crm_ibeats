<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use App\Traits\ValidationTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Http\JsonResponse;

abstract class BaseController extends Controller
{
    use ApiResponseTrait, ValidationTrait, FileUploadTrait;

    /**
     * Check data and return appropriate response.
     */
    protected function checkDataResponse($data, string $successMessage = 'Success', string $errorMessage = 'Data not found'): JsonResponse
    {
        if (!$data) {
            return $this->notFoundResponse($errorMessage);
        }

        return $this->successResponse($data, $successMessage);
    }

    /**
     * Legacy paginated response for backward compatibility.
     */
    protected function legacyPaginatedResponse($data, int $currentPage, int $perPage, int $total): JsonResponse
    {
        return response()->json([
            'status' => true,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'data' => $data,
        ]);
    }
}