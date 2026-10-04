<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->user = User::factory()->create();
});

function storedPhotoDimensions(string $path): array
{
    $info = getimagesizefromstring(Storage::disk('public')->get($path));

    return [$info[0], $info[1], $info['mime']];
}

test('guests cannot change a profile photo', function () {
    $this->post(route('profile-photo.store'), ['photo' => UploadedFile::fake()->image('me.jpg')])->assertRedirect(route('login'));
    $this->delete(route('profile-photo.destroy'))->assertRedirect(route('login'));
});

test('an uploaded photo is cropped to a square and stored as webp', function (int $width, int $height) {
    $this->actingAs($this->user)
        ->post(route('profile-photo.store'), ['photo' => UploadedFile::fake()->image('me.jpg', $width, $height)])
        ->assertSessionHasNoErrors()->assertRedirect();

    $path = $this->user->fresh()->profile_photo_path;
    expect($path)->toStartWith('profile-photos/')
        ->and(storedPhotoDimensions($path))->toBe([256, 256, 'image/webp']);
})->with(['landscape' => [1200, 600], 'portrait' => [300, 900], 'tiny' => [40, 40]]);

test('png and gif uploads are accepted', function (string $name) {
    $this->actingAs($this->user)
        ->post(route('profile-photo.store'), ['photo' => UploadedFile::fake()->image($name, 400, 400)])
        ->assertSessionHasNoErrors();

    expect(storedPhotoDimensions($this->user->fresh()->profile_photo_path)[2])->toBe('image/webp');
})->with(['me.png', 'me.gif']);

test('uploading again replaces the previous photo file', function () {
    $this->actingAs($this->user);
    $this->post(route('profile-photo.store'), ['photo' => UploadedFile::fake()->image('first.jpg')]);
    $first = $this->user->fresh()->profile_photo_path;

    $this->post(route('profile-photo.store'), ['photo' => UploadedFile::fake()->image('second.jpg')]);
    $second = $this->user->fresh()->profile_photo_path;

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
    expect(Storage::disk('public')->allFiles('profile-photos'))->toHaveCount(1);
});

test('invalid uploads are rejected and change nothing', function (callable $file) {
    $this->actingAs($this->user)
        ->post(route('profile-photo.store'), ['photo' => $file()])
        ->assertSessionHasErrors('photo');

    expect($this->user->fresh()->profile_photo_path)->toBeNull()
        ->and(Storage::disk('public')->allFiles())->toBeEmpty();
})->with([
    'a document' => [fn () => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')],
    'an svg' => [fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')],
    'too large a file' => [fn () => UploadedFile::fake()->image('big.jpg')->size(6000)],
    'too many pixels' => [fn () => UploadedFile::fake()->image('wide.png', 6001, 10)],
    'nothing at all' => [fn () => null],
]);

test('a file that claims to be an image but is not decodable is rejected cleanly', function () {
    $fake = UploadedFile::fake()->createWithContent('broken.png', 'not really a png');

    $this->actingAs($this->user)->post(route('profile-photo.store'), ['photo' => $fake])->assertSessionHasErrors('photo');

    expect($this->user->fresh()->profile_photo_path)->toBeNull();
});

test('a photo can be removed', function () {
    $this->actingAs($this->user)->post(route('profile-photo.store'), ['photo' => UploadedFile::fake()->image('me.jpg')]);
    $path = $this->user->fresh()->profile_photo_path;

    $this->delete(route('profile-photo.destroy'))->assertSessionHasNoErrors()->assertRedirect();

    expect($this->user->fresh()->profile_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('removing a photo when there is none is harmless', function () {
    $this->actingAs($this->user)->delete(route('profile-photo.destroy'))->assertRedirect();

    expect($this->user->fresh()->profile_photo_path)->toBeNull();
});

test('deleting an account deletes its photo', function () {
    $this->actingAs($this->user)->post(route('profile-photo.store'), ['photo' => UploadedFile::fake()->image('me.jpg')]);
    $path = $this->user->fresh()->profile_photo_path;

    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

    expect(User::find($this->user->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('the avatar url is shared with every place that shows a user', function () {
    $this->actingAs($this->user)->post(route('profile-photo.store'), ['photo' => UploadedFile::fake()->image('me.jpg')]);
    $user = $this->user->fresh();
    $board = Board::factory()->withMember($user)->create();

    expect($user->avatar)->toBe(Storage::disk('public')->url($user->profile_photo_path))
        ->and($user->toArray())->toHaveKey('avatar')->not->toHaveKey('profile_photo_path');

    $this->get(route('boards.edit', $board))->assertInertia(fn ($page) => $page
        ->where('auth.user.avatar', $user->avatar)
        ->where('members.data.0.avatar_url', $user->avatar));
});

test('users without a photo have no avatar url', function () {
    expect($this->user->avatar)->toBeNull();
});
