<?php

namespace App\Http\Controllers;

use App\Services\ApplicationHealth;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __construct(private ApplicationHealth $health) {}

    public function __invoke(): JsonResponse
    {
        $checks = $this->health->checks();
        $isReady = collect($checks)->every(
            fn (array $check): bool => $check['healthy'],
        );

        return response()->json([
            'status' => $isReady ? 'ready' : 'not_ready',
            'version' => config('app.version'),
            'checks' => collect($checks)->map(
                fn (array $check): bool => $check['healthy'],
            )->all(),
        ], $isReady ? 200 : 503);
    }
}
