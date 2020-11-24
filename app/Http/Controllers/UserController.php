<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function settings()
    {
        return Inertia::render('Settings/Show');
    }
}
