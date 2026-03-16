<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;
use Throwable;

class FileUploadService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    public function maxFileKilobytes(): int
    {
        return (int) config('uploads.max_file_kb', 10240);
    }

    public function resolveDisk(): string
    {
        if (app()->environment('local')) {
            return (string) config('uploads.local_disk', 'public');
        }

        return (string) config('uploads.remote_disk', 's3');
    }

    public function store(UploadedFile $file, string $directory): string
    {
        $disk = $this->resolveDisk();
        $directory = trim($directory, '/');
        $extension = strtolower(
            $file->guessExtension()
                ?: $file->getClientOriginalExtension()
                ?: 'bin'
        );
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $directory.'/'.$filename;

        if ($this->isImage($file)) {
            try {
                $image = $this->imageManager->read($file->getRealPath());
                $image->scaleDown(width: 2000, height: 2000);
                $encoded = $image->encode(
                    new AutoEncoder(quality: $this->imageQuality())
                );

                Storage::disk($disk)->put(
                    $path,
                    (string) $encoded,
                    ['visibility' => 'public']
                );

                return $path;
            } catch (Throwable) {
                // Fallback to original file if compression fails.
            }
        }

        Storage::disk($disk)->putFileAs(
            $directory,
            $file,
            $filename,
            ['visibility' => 'public']
        );

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        $disks = [
            $this->resolveDisk(),
        ];

        if (! app()->environment('local')) {
            $disks[] = (string) config('uploads.local_disk', 'public');
        }

        $disks = array_unique($disks);

        foreach ($disks as $disk) {
            if (! config("filesystems.disks.$disk")) {
                continue;
            }

            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (Throwable) {
                // Ignore delete failures on unavailable disks (ex: missing S3 creds in local).
            }
        }
    }

    private function imageQuality(): int
    {
        $quality = (int) config('uploads.image_quality', 75);
        return max(0, min($quality, 100));
    }

    private function isImage(UploadedFile $file): bool
    {
        $mime = $file->getMimeType();
        return is_string($mime) && str_starts_with($mime, 'image/');
    }
}
