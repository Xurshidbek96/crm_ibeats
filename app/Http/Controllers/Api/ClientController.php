<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Client;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends BaseController
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of clients.
     */
    public function index(): JsonResponse
    {
        $clients = Client::orderBy('id', 'DESC')->get();

        $clients->transform(function ($client) {
            $client->file_url = $client->file_url;
            $client->file_passport_url = $client->file_passport_url;
            $client->phone = $client->formatted_phones;
            return $client;
        });

        return $this->successResponse($clients);
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'passport' => 'nullable|size:9|unique:clients',
            'passport_status' => 'nullable|string',
            'date_of_issue' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|integer',
            'number_of_children' => 'nullable|integer',
            'email' => 'nullable|email|unique:clients',
            'phones' => 'nullable|array',
            'address' => 'nullable|string',
            'file' => 'nullable|mimes:png,jpg,pdf|max:2048',
            'file_passport' => 'nullable|mimes:png,jpg,pdf|max:2048',
        ]);

        // Handle file uploads
        if ($request->hasFile('file')) {
            $validatedData['file'] = $this->fileUploadService->uploadFile($request->file('file'));
        }

        if ($request->hasFile('file_passport')) {
            $validatedData['file_passport'] = $this->fileUploadService->uploadFile($request->file('file_passport'));
        }

        $client = Client::create($validatedData);

        return $this->successResponse($client, 'Client created successfully', 201);
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client): JsonResponse
    {
        $client->phone = $client->formatted_phones;
        $client->file_url = $client->file_url;
        $client->file_passport_url = $client->file_passport_url;
        $client->orders = $client->orders;

        return $this->successResponse($client);
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'passport' => 'nullable|size:9|unique:clients,passport,' . $client->id,
            'passport_status' => 'nullable|string',
            'date_of_issue' => 'nullable|date',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|integer',
            'number_of_children' => 'nullable|integer',
            'email' => 'nullable|email|unique:clients,email,' . $client->id,
            'phones' => 'nullable|array',
            'address' => 'nullable|string',
            'file' => 'nullable|mimes:png,jpg,pdf|max:2048',
            'file_passport' => 'nullable|mimes:png,jpg,pdf|max:2048',
        ]);

        // Handle file uploads and deletions
        if ($request->hasFile('file')) {
            if ($client->file) {
                $this->fileUploadService->deleteFile($client->file);
            }
            $validatedData['file'] = $this->fileUploadService->uploadFile($request->file('file'));
        }

        if ($request->hasFile('file_passport')) {
            if ($client->file_passport) {
                $this->fileUploadService->deleteFile($client->file_passport);
            }
            $validatedData['file_passport'] = $this->fileUploadService->uploadFile($request->file('file_passport'));
        }

        $client->update($validatedData);

        return $this->successResponse($client, 'Client updated successfully');
    }

    /**
     * Remove the specified client.
     */
    public function destroy(Client $client): JsonResponse
    {
        // Delete associated files
        if ($client->file) {
            $this->fileUploadService->deleteFile($client->file);
        }
        
        if ($client->file_passport) {
            $this->fileUploadService->deleteFile($client->file_passport);
        }

        $deleted = $client->delete();

        return $this->checkDataResponse($deleted, 'Client deleted successfully', 'Failed to delete client');
    }
}
