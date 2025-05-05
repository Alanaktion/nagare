<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\ImageManager;

trait HasProfilePhoto
{
    /**
     * Update the user's profile photo.
     */
    public function updateProfilePhoto(UploadedFile $photo, string $storagePath = 'profile-photos'): void
    {
        tap($this->profile_photo_path, function ($previous) use ($photo, $storagePath) {
            try {
                $data = ImageManager::gd()
                    ->read($photo->getPathname())
                    ->scaleDown(384, 384)
                    ->encode(new WebpEncoder(strip: true));
                $path = $storagePath.'/'.Str::random(40).'.webp';
                $this->getDisk()->writeStream($path, $data->toFilePointer());
            } catch (DriverException) {
                $path = $photo->storePublicly($storagePath, [
                    'disk' => $this->profilePhotoDisk(),
                ]);
            }
            $this->forceFill([
                'profile_photo_path' => $path,
            ])->save();

            if ($previous) {
                $this->getDisk()->delete($previous);
            }
        });
    }

    /**
     * Delete the user's profile photo.
     */
    public function deleteProfilePhoto(): void
    {
        if ($this->profile_photo_path === null) {
            return;
        }

        $this->getDisk()->delete($this->profile_photo_path);

        $this->forceFill([
            'profile_photo_path' => null,
        ])->save();
    }

    /**
     * Get the URL to the user's profile photo.
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(function (): ?string {
            return $this->profile_photo_path
                    ? $this->getDisk()->url($this->profile_photo_path)
                    : null;
        });
    }

    protected function getDisk(): FilesystemAdapter
    {
        return Storage::disk($this->profilePhotoDisk());
    }

    /**
     * Get the disk that profile photos should be stored on.
     */
    protected function profilePhotoDisk(): string
    {
        return isset($_ENV['VAPOR_ARTIFACT_NAME']) ? 's3' : config('filesystems.profile_photos', 'public');
    }
}
