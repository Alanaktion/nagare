<?php

namespace App\Http\Controllers;

use App\Actions\Users\DeleteProfilePhoto;
use App\Actions\Users\UpdateProfilePhoto;
use App\Http\Requests\Settings\ProfilePhotoUpdateRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProfilePhotoController extends Controller
{
    public function store(ProfilePhotoUpdateRequest $request, #[CurrentUser] User $user, UpdateProfilePhoto $updatePhoto): RedirectResponse
    {
        $updatePhoto->handle($user, $request->file('photo'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile photo updated.')]);

        return back();
    }

    public function destroy(#[CurrentUser] User $user, DeleteProfilePhoto $deletePhoto): RedirectResponse
    {
        $deletePhoto->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile photo removed.')]);

        return back();
    }
}
