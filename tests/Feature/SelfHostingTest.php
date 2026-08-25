<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Tester\CommandTester;

uses(LazilyRefreshDatabase::class);

it('reports application readiness without exposing diagnostic details', function () {
    config()->set('app.version', 'test-version');

    $this->get(route('ready'))
        ->assertSuccessful()
        ->assertExactJson([
            'status' => 'ready',
            'version' => 'test-version',
            'checks' => [
                'database' => true,
                'migrations' => true,
                'storage' => true,
            ],
        ]);

    expect(File::isDirectory(storage_path('app/public/wallpapers')))->toBeTrue();
});

it('runs the self-hosting diagnostic command successfully', function () {
    config()->set([
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        'app.url' => 'https://wallai.example.com',
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'session.driver' => 'array',
    ]);

    $tester = new CommandTester(Artisan::all()['wallai:doctor']);

    expect($tester->execute([]))->toBe(0)
        ->and($tester->getDisplay())->toContain('WallAI is ready');
});

it('rejects an unsafe production deployment configuration', function () {
    config()->set([
        'app.key' => null,
        'app.env' => 'testing',
        'app.debug' => true,
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'session.driver' => 'array',
    ]);

    $tester = new CommandTester(Artisan::all()['wallai:doctor']);

    expect($tester->execute(['--deployment' => true]))->toBe(1)
        ->and($tester->getDisplay())->toContain('WallAI is not ready');
});

it('rejects insecure cookies for an HTTPS deployment', function () {
    config()->set([
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        'app.url' => 'https://wallai.example.com',
        'app.env' => 'production',
        'app.debug' => false,
        'app.self_hosted_domain' => 'wallai.example.com',
        'app.trusted_hosts' => ['wallai.example.com'],
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'session.driver' => 'array',
        'session.secure' => false,
    ]);

    $tester = new CommandTester(Artisan::all()['wallai:doctor']);

    expect($tester->execute(['--deployment' => true]))->toBe(1)
        ->and($tester->getDisplay())->toContain('SESSION_SECURE_COOKIE must be true');
});
