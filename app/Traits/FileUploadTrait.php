<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FileUploadTrait
{
    /**
     * Upload a single file.
     */
    protected function uploadFile(UploadedFile $file, string $directory = 'uploads', string $disk = 'public'): string
    {
        $filename = $this->generateUniqueFilename($file);
        return $file->storeAs($directory, $filename, $disk);
    }

    /**
     * Upload multiple files.
     */
    protected function uploadFiles(array $files, string $directory = 'uploads', string $disk = 'public'): array
    {
        $uploadedFiles = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadFile($file, $directory, $disk);
            }
        }
        
        return $uploadedFiles;
    }

    /**
     * Upload file from request.
     */
    protected function uploadFromRequest(Request $request, string $fieldName, string $directory = 'uploads', string $disk = 'public'): ?string
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            if ($file->isValid()) {
                return $this->uploadFile($file, $directory, $disk);
            }
        }
        
        return null;
    }

    /**
     * Upload multiple files from request.
     */
    protected function uploadMultipleFromRequest(Request $request, string $fieldName, string $directory = 'uploads', string $disk = 'public'): array
    {
        $uploadedFiles = [];
        
        if ($request->hasFile($fieldName)) {
            $files = $request->file($fieldName);
            
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $uploadedFiles[] = $this->uploadFile($file, $directory, $disk);
                    }
                }
            } elseif ($files instanceof UploadedFile && $files->isValid()) {
                $uploadedFiles[] = $this->uploadFile($files, $directory, $disk);
            }
        }
        
        return $uploadedFiles;
    }

    /**
     * Delete a file.
     */
    protected function deleteFile(string $filePath, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($filePath)) {
            return Storage::disk($disk)->delete($filePath);
        }
        
        return false;
    }

    /**
     * Delete multiple files.
     */
    protected function deleteFiles(array $filePaths, string $disk = 'public'): array
    {
        $results = [];
        
        foreach ($filePaths as $filePath) {
            $results[$filePath] = $this->deleteFile($filePath, $disk);
        }
        
        return $results;
    }

    /**
     * Replace an existing file with a new one.
     */
    protected function replaceFile(?string $oldFilePath, UploadedFile $newFile, string $directory = 'uploads', string $disk = 'public'): string
    {
        // Delete old file if exists
        if ($oldFilePath) {
            $this->deleteFile($oldFilePath, $disk);
        }
        
        // Upload new file
        return $this->uploadFile($newFile, $directory, $disk);
    }

    /**
     * Generate a unique filename.
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = Str::slug($originalName);
        
        return $sanitizedName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    /**
     * Get file URL.
     */
    protected function getFileUrl(string $filePath, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($filePath);
    }

    /**
     * Check if file exists.
     */
    protected function fileExists(string $filePath, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($filePath);
    }

    /**
     * Get file size in bytes.
     */
    protected function getFileSize(string $filePath, string $disk = 'public'): int
    {
        return Storage::disk($disk)->size($filePath);
    }

    /**
     * Validate file type.
     */
    protected function validateFileType(UploadedFile $file, array $allowedTypes): bool
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());
        
        return in_array($mimeType, $allowedTypes) || in_array($extension, $allowedTypes);
    }

    /**
     * Validate file size.
     */
    protected function validateFileSize(UploadedFile $file, int $maxSizeInBytes): bool
    {
        return $file->getSize() <= $maxSizeInBytes;
    }

    /**
     * Get common image types.
     */
    protected function getImageTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ];
    }

    /**
     * Get common document types.
     */
    protected function getDocumentTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'txt'
        ];
    }
}