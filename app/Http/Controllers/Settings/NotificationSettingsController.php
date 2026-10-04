<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\NotificationSettingsUpdateRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    /**
     * Show the user's notification settings.
     */
    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('settings/Notifications', [
            'emailNotifications' => $user->email_notifications,
        ]);
    }

    /**
     * Update the user's notification settings.
     */
    public function update(NotificationSettingsUpdateRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $user->forceFill(['email_notifications' => $request->boolean('email_notifications')])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification settings saved.')]);

        return back();
    }
}
