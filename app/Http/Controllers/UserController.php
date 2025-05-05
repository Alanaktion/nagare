<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        return Inertia::render('user/Index', [
            'users' => User::all(),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load('boards', 'assignedIssues', 'assignedIssues.status');

        return Inertia::render('user/Show', [
            'user' => $user,
        ]);
    }
}
