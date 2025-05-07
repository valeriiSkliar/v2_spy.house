<?php

namespace App\Services\App;

use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * Upload an image to the specified directory on the given disk.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @param string $directory
     * @param string $disk
     * @return string|null
     */
    public function upload($image, string $directory, string $disk = 'public'): ?string
    {
        $path = $image->store($directory, $disk);

        return $path ?: null;
    }

    /**
     * Delete an image from the specified disk.
     *
     * @param string|null $imagePath
     * @param string $disk
     * @return bool
     */
    public function delete(?string $imagePath, string $disk = 'public'): bool
    {
        if (!$imagePath) {
            return false;
        }

        $relativePath = str_replace('/storage/', '', $imagePath);
        if (Storage::disk($disk)->exists($relativePath)) {
            return Storage::disk($disk)->delete($relativePath);
        }

        return false;
    }

    /**
     * Replace an existing image with a new one.
     *
     * @param \Illuminate\Http\UploadedFile $newImage
     * @param string|null $existingImagePath
     * @param string $directory
     * @param string $disk
     * @return string|null
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
     *
     * @param string $imagePath
     * @param string $disk
     * @return bool
     */
    public function exists(string $imagePath, string $disk = 'public'): bool
    {
        $relativePath = str_replace('/storage/', '', $imagePath);

        return Storage::disk($disk)->exists($relativePath);
    }
}
