<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DeleteProfilePhoto
{
    /**
     * Remove the user's profile photo, falling back to their initials.
     */
    public function handle(User $user): void
    {
        if ($user->profile_photo_path === null) {
            return;
        }

        Storage::disk(User::PROFILE_PHOTO_DISK)->delete($user->profile_photo_path);

        $user->forceFill(['profile_photo_path' => null])->save();
    }
}
