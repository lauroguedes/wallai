<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class AbstractImageGenerator
{
    /**
     * @throws \Exception
     */
    protected function httpClient(
        string $url,
        string $token,
        array $params,
        string $method = 'get'
    ): Response
    {
        try {
            return Http::withToken($token)
                ->{$method}($url, $params)
                ->throw();
        } catch (\Throwable $th) {
            $this->handleErrors($th);
        }
    }

    /**
     * @throws \Exception
     */
    protected function handleErrors(\Throwable $error): void
    {
        throw new \Exception($error->getMessage());
    }

    abstract public function generate(string $prompt, string $style): ?array;
    abstract protected function mountPrompt(...$args): string;
}
