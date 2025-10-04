<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Upload a file to the public files directory.
     */
    public function uploadFile(UploadedFile $file, string $directory = 'files'): string
    {
        $fileName = time() . '-' . $file->getClientOriginalName();
        $file->move(public_path($directory), $fileName);
        
        return $fileName;
    }

    /**
     * Delete a file from the public files directory.
     */
    public function deleteFile(string $fileName, string $directory = 'files'): bool
    {
        $filePath = public_path($directory . '/' . $fileName);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }

    /**
     * Get the full URL for a file.
     */
    public function getFileUrl(string $fileName, string $directory = 'files'): string
    {
        return url($directory . '/' . $fileName);
    }

    /**
     * Check if a file exists.
     */
    public function fileExists(string $fileName, string $directory = 'files'): bool
    {
        return file_exists(public_path($directory . '/' . $fileName));
    }
}