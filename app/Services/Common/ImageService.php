<?php

namespace App\Services\Common;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Upload an image to the specified directory on the given disk.
     *
     * @param  \Illuminate\Http\UploadedFile  $image
     */
    public function upload($image, string $directory, string $disk = 'public'): ?string
    {
        $path = $image->store($directory, $disk);

        return $path ?: null;
    }

    /**
     * Delete an image from the specified disk.
     */
    public function delete(?string $imagePath, string $disk = 'public'): bool
    {
        if (! $imagePath) {
            return false;
        }

        // Normalize the path - remove domain, /storage/ prefix if present
        $relativePath = $imagePath;

        // Remove full URL if present
        if (str_contains($imagePath, '://')) {
            $urlParts = parse_url($imagePath);
            $relativePath = $urlParts['path'] ?? '';
        }

        // Remove /storage/ prefix if present
        $relativePath = str_replace('/storage/', '', $relativePath);

        if (Storage::disk($disk)->exists($relativePath)) {
            $deleted = Storage::disk($disk)->delete($relativePath);

            if ($deleted) {
                Log::info('Image successfully deleted', [
                    'original_path' => $imagePath,
                    'normalized_path' => $relativePath,
                    'disk' => $disk,
                ]);
            } else {
                Log::warning('Failed to delete existing image', [
                    'original_path' => $imagePath,
                    'normalized_path' => $relativePath,
                    'disk' => $disk,
                ]);
            }

            return $deleted;
        }

        Log::warning('Image file not found for deletion', [
            'original_path' => $imagePath,
            'normalized_path' => $relativePath,
            'disk' => $disk,
        ]);

        return false;
    }

    /**
     * Replace an existing image with a new one.
     *
     * @param  \Illuminate\Http\UploadedFile  $newImage
     */
    public function replace($newImage, ?string $existingImagePath, string $directory, string $disk = 'public'): ?string
    {
        // Delete the existing image if it exists
        if ($existingImagePath) {
            $this->delete($existingImagePath, $disk);
        }

        // Upload the new image
        return $this->upload($newImage, $directory, $disk);
    }

    /**
     * Check if an image exists in the storage.
     */
    public function exists(string $imagePath, string $disk = 'public'): bool
    {
        $relativePath = str_replace('/storage/', '', $imagePath);

        return Storage::disk($disk)->exists($relativePath);
    }
}
