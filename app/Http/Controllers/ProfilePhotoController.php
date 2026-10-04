<?php

namespace App\Http\Controllers;

use App\Actions\Users\DeleteProfilePhoto;
use App\Actions\Users\UpdateProfilePhoto;
use App\Http\Requests\Settings\ProfilePhotoUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfilePhotoController extends Controller
{
    public function store(ProfilePhotoUpdateRequest $request, UpdateProfilePhoto $updatePhoto): RedirectResponse
    {
        $updatePhoto->handle($request->user(), $request->file('photo'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile photo updated.')]);

        return back();
    }

    public function destroy(Request $request, DeleteProfilePhoto $deletePhoto): RedirectResponse
    {
        $deletePhoto->handle($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile photo removed.')]);

        return back();
    }
}
