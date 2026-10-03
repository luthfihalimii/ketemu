<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ItemPhotoService
{
    /**
     * Disk legacy sebelum R2. Jangan dipakai langsung — gunakan disk().
     */
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
     * Disk aktif foto barang: 'public' (lokal) atau 'r2' (Cloudflare R2).
     * Diatur via FILESYSTEM_PHOTOS_DISK agar ganti storage tanpa migrasi DB.
     */
    public static function disk(): string
    {
        $disk = (string) config('ketemupens.photos.disk', 'public');

        return $disk !== '' ? $disk : 'public';
    }

    /**
     * URL publik sebuah foto (lokal /storage atau R2_PUBLIC_URL + CDN).
     */
    public function url(string $path): string
    {
        return Storage::disk(self::disk())->url($path);
    }

    /**
     * Store an uploaded photo, re-encoding it to strip EXIF/GPS metadata.
     *
     * The filename is always generated server-side; the client-provided name is
     * never trusted.
     */
    public function store(UploadedFile $file, int $itemId): string
    {
        // Nama file sepenuhnya acak agar tidak bisa ditebak berurutan.
        $filename = sprintf('%s-%d.webp', Str::lower(Str::random(24)), $itemId);
        $path = self::DIRECTORY.'/'.$filename;

        try {
            $source = imagecreatefromstring($file->getContent());
            if ($source === false) {
                throw new \RuntimeException('Unable to decode image.');
            }
            if ($file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file->getPathname());
                $orientation = is_array($exif) ? ($exif['Orientation'] ?? 1) : 1;
                if (in_array($orientation, [2, 4, 5, 7], true)) {
                    imageflip($source, IMG_FLIP_HORIZONTAL);
                }
                $angle = match ($orientation) {
                    3, 4 => 180,
                    5, 6 => -90,
                    7, 8 => 90,
                    default => 0,
                };
                if ($angle !== 0) {
                    $source = imagerotate($source, $angle, 0);
                    if ($source === false) {
                        throw new \RuntimeException('Unable to orient image.');
                    }
                }
            }
            $ratio = min(1, 1600 / imagesx($source), 1600 / imagesy($source));
            $image = imagescale($source, max(1, (int) (imagesx($source) * $ratio)), max(1, (int) (imagesy($source) * $ratio)));
            if ($image === false) {
                throw new \RuntimeException('Unable to resize image.');
            }
            $stream = fopen('php://temp', 'w+b');
            try {
                if ($stream === false || ! imagewebp($image, $stream, 80)) {
                    throw new \RuntimeException('Unable to encode image.');
                }
                rewind($stream);
                if (Storage::disk(self::disk())->put($path, $stream, 'public')) {
                    return $path;
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Image processing failed.', [
                'item_id' => $itemId,
                'message' => $exception->getMessage(),
            ]);
        }

        $this->delete($path);
        throw ValidationException::withMessages(['photo' => 'Foto tidak dapat diproses atau disimpan. Silakan unggah foto lain.']);
    }

    public function archive(string $path): void
    {
        $contents = Storage::disk(self::disk())->get($path);
        if (! Storage::disk('local')->put('moderation/'.$path, $contents)) {
            throw ValidationException::withMessages(['reason' => 'Foto tidak dapat diarsipkan. Laporan belum dinonaktifkan.']);
        }
    }

    public function restore(string $path): void
    {
        $contents = Storage::disk('local')->get('moderation/'.$path);
        if (! Storage::disk(self::disk())->put($path, $contents, 'public')) {
            throw ValidationException::withMessages(['reason' => 'Foto tidak dapat dipulihkan. Silakan coba lagi.']);
        }
    }

    /**
     * Remove a stored photo. Missing files are ignored so cleanup can be
     * called repeatedly without bookkeeping.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk(self::disk())->delete($path);
    }
}
