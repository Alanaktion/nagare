<?php

use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;

function forwardedClientIp(string $remoteAddr): string
{
    Route::get('/_proxy-client-ip', fn (Request $request) => $request->ip());

    return test()
        ->withServerVariables(['REMOTE_ADDR' => $remoteAddr])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.5'])
        ->get('/_proxy-client-ip')
        ->getContent();
}

test('the client IP is read from forwarded headers sent by localhost and docker networks are trusted by default', function (string $proxy) {
    expect(forwardedClientIp($proxy))->toBe('203.0.113.5');
})->with(['127.0.0.1', '::1', '172.17.0.2', '172.28.5.9']);

test('forwarded headers from other addresses are ignored by default', function () {
    expect(forwardedClientIp('198.51.100.7'))->toBe('198.51.100.7');
});

test('the trusted proxies env var accepts a comma-separated list or a wildcard', function () {
    expect(config('app.trusted_proxies'))->toBe(['127.0.0.1', '::1', '172.16.0.0/12']);

    $repository = Env::getRepository();

    try {
        $repository->set('TRUSTED_PROXIES', ' 10.0.0.0/8 , 192.168.1.1 ');
        expect((require base_path('config/app.php'))['trusted_proxies'])->toBe(['10.0.0.0/8', '192.168.1.1']);

        $repository->clear('TRUSTED_PROXIES');
        $repository->set('TRUSTED_PROXIES', '*');
        expect((require base_path('config/app.php'))['trusted_proxies'])->toBe('*');
    } finally {
        $repository->clear('TRUSTED_PROXIES');
    }
});
