<?php

use Illuminate\Support\Env;

function reverbPublishOptions(array $env): array
{
    $keys = ['REVERB_HOST', 'REVERB_PORT', 'REVERB_SCHEME', 'REVERB_PUBLISH_HOST', 'REVERB_PUBLISH_PORT', 'REVERB_PUBLISH_SCHEME'];

    $original = array_combine($keys, array_map(fn (string $key) => Env::get($key), $keys));

    foreach ($keys as $key) {
        isset($env[$key]) ? Env::getRepository()->set($key, $env[$key]) : Env::getRepository()->clear($key);
    }

    $options = (require base_path('config/broadcasting.php'))['connections']['reverb']['options'];

    foreach ($original as $key => $value) {
        $value === null ? Env::getRepository()->clear($key) : Env::getRepository()->set($key, (string) $value);
    }

    return $options;
}

test('events are published to the public reverb address by default', function () {
    expect(reverbPublishOptions(['REVERB_HOST' => 'reverb.example.com', 'REVERB_PORT' => '443', 'REVERB_SCHEME' => 'https']))
        ->toMatchArray(['host' => 'reverb.example.com', 'port' => '443', 'scheme' => 'https', 'useTLS' => true]);
});

test('events can be published to a separate internal reverb address', function () {
    expect(reverbPublishOptions([
        'REVERB_HOST' => 'reverb.example.com', 'REVERB_PORT' => '443', 'REVERB_SCHEME' => 'https',
        'REVERB_PUBLISH_HOST' => 'reverb', 'REVERB_PUBLISH_PORT' => '8000', 'REVERB_PUBLISH_SCHEME' => 'http',
    ]))->toMatchArray(['host' => 'reverb', 'port' => '8000', 'scheme' => 'http', 'useTLS' => false]);
});
