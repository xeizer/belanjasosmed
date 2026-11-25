<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resizes an uploaded product image to fit 800x800 px, converts it to WebP with
 * PHP's GD extension and stores it on the public disk under a server-generated name.
 */
class StoreProductImage
{
    public const string DISK = 'public';

    public const string DIRECTORY = 'products';

    public const int MAX_DIMENSION = 800;

    private const int WEBP_QUALITY = 82;

    /**
     * Store the image and return its path on the public disk.
     *
     * @throws RuntimeException When the file cannot be decoded as an image.
     */
    public function handle(UploadedFile $file): string
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('Uploaded file is not a readable image.');
        }

        $source = $this->applyExifOrientation($source, $contents);
        $resized = $this->resizeToFit($source);

        ob_start();
        imagewebp($resized, null, self::WEBP_QUALITY);
        $webp = (string) ob_get_clean();

        $path = self::DIRECTORY.'/'.Str::random(40).'.webp';

        Storage::disk(self::DISK)->put($path, $webp);

        return $path;
    }

    /**
     * Delete a previously stored image, ignoring empty paths.
     */
    public function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Scale the image down (never up) so that it fits within MAX_DIMENSION, keeping transparency.
     */
    private function resizeToFit(\GdImage $source): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_DIMENSION / max($width, $height));

        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $target;
    }

    /**
     * Rotate JPEG photos (e.g. taken with a phone) according to their EXIF orientation.
     */
    private function applyExifOrientation(\GdImage $image, string $contents): \GdImage
    {
        if (! function_exists('exif_read_data') || ! str_starts_with($contents, "\xFF\xD8")) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contents));
        $rotation = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $rotation === 0 ? $image : (imagerotate($image, $rotation, 0) ?: $image);
    }
}
