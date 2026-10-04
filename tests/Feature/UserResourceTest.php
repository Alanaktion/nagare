<?php

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

test('user resource exposes only public profile fields', function () {
    $user = User::factory()->make(['id' => 7]);

    $data = (new UserResource($user))->resolve(new Request);

    expect($data)->toBe([
        'id' => 7,
        'name' => $user->name,
        'email' => $user->email,
        'avatar_url' => null,
    ]);
});
