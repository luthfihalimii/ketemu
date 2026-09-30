<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ItemPhotoService
{
    public const DISK = 'public';

    public const DIRECTORY = 'items';

    public const MAX_KILOBYTES = 5120;

    /**
     * Allowed mime types for found item photos.
     *
     * @var array<int, string>
     */
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Store an uploaded photo, re-encoding it to strip EXIF/GPS metadata.
     *
     * The filename is always generated server-side; the client-provided name is
     * never trusted.
     */
    public function store(UploadedFile $file, int $itemId): string
    {
        $filename = sprintf('%d-%s.webp', $itemId, Str::lower(Str::random(16)));
        $path = self::DIRECTORY.'/'.$filename;

        try {
            // Re-encoding also drops metadata (EXIF, GPS) from the original file.
            $stored = Image::fromUpload($file)
                ->orient()
                ->cover(1600, 1600)
                ->toWebp()
                ->quality(80)
                ->storePubliclyAs(self::DIRECTORY, $filename, self::DISK);

            if ($stored !== false) {
                return $path;
            }
        } catch (Throwable $exception) {
            Log::warning('Image processing failed, storing sanitised original.', [
                'item_id' => $itemId,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            Image::fromUpload($file)->orient()->toWebp()->storePubliclyAs(
                self::DIRECTORY,
                $filename,
                self::DISK,
            );

            return $path;
        } catch (Throwable $exception) {
            Log::error('Unable to process uploaded photo.', [
                'item_id' => $itemId,
                'message' => $exception->getMessage(),
            ]);
        }

        // Last resort: move the validated upload without any transformation.
        return $file->storePubliclyAs(self::DIRECTORY, $filename, self::DISK);
    }
}
