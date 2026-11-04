<?php

namespace App\Services;

use App\Support\Uploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores product gallery images in three sizes under /uploads/products/gallery/{original,medium,thumb}.
 */
class ImageService
{
    private const BASE = 'products/gallery';

    /** Longest side, in pixels, of each derived size. */
    private const SIZES = ['medium' => 600, 'thumb' => 200];

    /**
     * @return string web path of the original image (e.g. /uploads/products/gallery/original/abc.jpg)
     */
    public function storeProductImage(UploadedFile $file): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $name = 'product_'.Str::lower(Str::random(20)).'.'.$extension;
        $disk = Storage::disk('uploads');

        $contents = (string) file_get_contents($file->getRealPath());
        $disk->put(self::BASE."/original/{$name}", $contents);

        foreach (self::SIZES as $size => $max) {
            $disk->put(self::BASE."/{$size}/{$name}", $this->resize($contents, $extension, $max) ?? $contents);
        }

        return '/uploads/'.self::BASE."/original/{$name}";
    }

    /**
     * Remove the original and its derived sizes.
     */
    public function deleteProductImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        Uploads::delete($path);

        foreach (array_keys(self::SIZES) as $size) {
            Uploads::delete(str_replace('/original/', "/{$size}/", $path));
        }
    }

    /**
     * Scale the image down so that its longest side is at most $max pixels.
     * Returns null when the image cannot be processed (the caller keeps the original bytes).
     */
    private function resize(string $contents, string $extension, int $max): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $max / max($width, $height));

        $image = $scale < 1
            ? imagescale($source, (int) round($width * $scale), (int) round($height * $scale))
            : $source;

        if ($image === false) {
            return null;
        }

        ob_start();
        $written = match ($extension) {
            'png' => imagepng($image),
            'gif' => imagegif($image),
            'webp' => function_exists('imagewebp') && imagewebp($image, null, 85),
            default => imagejpeg($image, null, 85),
        };
        $output = (string) ob_get_clean();

        return $written && $output !== '' ? $output : null;
    }
}
