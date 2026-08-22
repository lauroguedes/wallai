<?php

namespace App\Services;

use App\Enums\GenerationProvider;
use App\Exceptions\MissingAiCredentialsException;
use App\Models\AiProviderSetting;
use Closure;
use Illuminate\Support\Str;
use Laravel\Ai\Ai;

class RuntimeAiProvider
{
    public function __construct(private AiProviderSettings $settings) {}

    /**
     * Run an AI operation with an isolated, short-lived provider configuration.
     *
     * @template TResult
     *
     * @param  Closure(string): TResult  $callback
     * @return TResult
     */
    public function using(
        GenerationProvider $provider,
        ?AiProviderSetting $settings,
        Closure $callback,
    ): mixed {
        $apiKey = $this->settings->effectiveKey($provider, $settings);

        if ($apiKey === null) {
            throw new MissingAiCredentialsException($provider);
        }

        $alias = 'wallai-'.$provider->value.'-'.Str::lower((string) Str::ulid());
        $providers = (array) config('ai.providers', []);

        $providers[$alias] = [
            'driver' => $provider->value,
            'key' => $apiKey,
        ];

        config(['ai.providers' => $providers]);
        Ai::forgetInstance($alias);

        try {
            return $callback($alias);
        } finally {
            Ai::forgetInstance($alias);

            $providers = (array) config('ai.providers', []);
            unset($providers[$alias]);
            config(['ai.providers' => $providers]);
        }
    }
}
