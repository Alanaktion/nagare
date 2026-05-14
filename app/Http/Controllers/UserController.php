<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

final class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): Response
    {
        return Inertia::render('user/Index', [
            'users' => User::all(),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response
    {
        $user->load('boards', 'assignedIssues', 'assignedIssues.status');

        return Inertia::render('user/Show', [
            'user' => $user,
        ]);
    }
}
