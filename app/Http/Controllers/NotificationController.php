<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Notifications per page.
     */
    public const int PER_PAGE = 20;

    /**
     * The user's notifications, newest first.
     */
    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('notifications/Index', [
            'notifications' => NotificationResource::collection(
                $user->notifications()->latest()->latest('id')->paginate(self::PER_PAGE)
            ),
        ]);
    }

    /**
     * Mark all of the user's notifications as read.
     */
    public function update(#[CurrentUser] User $user): RedirectResponse
    {
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
