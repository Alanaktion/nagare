<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateProfilePhoto
{
    /**
     * Size, in pixels, of the square profile photo.
     */
    public const int SIZE = 256;

    public const int QUALITY = 80;

    /**
     * Replace the user's photo with the uploaded image, cropped to a square,
     * resized and stored as WebP. The previous photo is deleted.
     *
     * @throws ValidationException If the image can't be processed or stored.
     */
    public function handle(User $user, UploadedFile $upload): void
    {
        try {
            $path = Image::fromUpload($upload)
                ->orient()
                ->cover(self::SIZE, self::SIZE)
                ->toWebp()
                ->quality(self::QUALITY)
                ->storePublicly('profile-photos', User::PROFILE_PHOTO_DISK);
        } catch (\Throwable $exception) {
            report($exception);
            $path = false;
        }

        if ($path === false) {
            throw ValidationException::withMessages(['photo' => __('The photo could not be processed. Try a different image.')]);
        }

        $previous = $user->profile_photo_path;

        $user->forceFill(['profile_photo_path' => $path])->save();

        if ($previous !== null) {
            Storage::disk(User::PROFILE_PHOTO_DISK)->delete($previous);
        }
    }
}
