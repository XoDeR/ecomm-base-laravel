<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores files on the public "uploads" disk and returns web paths such as /uploads/categories/abc.jpg.
 */
class Uploads
{
    public static function store(UploadedFile $file, string $directory): string
    {
        $name = Str::random(20).'.'.($file->guessExtension() ?: $file->getClientOriginalExtension());

        Storage::disk('uploads')->putFileAs($directory, $file, $name);

        return '/uploads/'.trim($directory, '/').'/'.$name;
    }

    /**
     * Delete a previously stored file. Paths outside /uploads are ignored.
     */
    public static function delete(?string $path): void
    {
        if (! $path || ! str_starts_with($path, '/uploads/')) {
            return;
        }

        Storage::disk('uploads')->delete(substr($path, strlen('/uploads/')));
    }
}
