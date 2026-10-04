<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * Profile photo storage for the User model.
 *
 * @property string|null $profile_photo_path
 * @property-read string|null $avatar
 */
trait HasProfilePhoto
{
    /**
     * The disk profile photos are stored on.
     */
    public const string PROFILE_PHOTO_DISK = 'public';

    /**
     * Delete the stored photo when the user is deleted.
     */
    public static function bootHasProfilePhoto(): void
    {
        static::deleting(function (self $user): void {
            if ($user->profile_photo_path !== null) {
                Storage::disk(self::PROFILE_PHOTO_DISK)->delete($user->profile_photo_path);
            }
        });
    }

    /**
     * The URL of the user's profile photo, or null if they have none.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->profile_photo_path === null
            ? null
            : Storage::disk(self::PROFILE_PHOTO_DISK)->url($this->profile_photo_path));
    }
}
