<?php

namespace App\Services;

use App\Exceptions\ServiceGeneratorException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class AbstractTextGenerator
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
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'wait',
                ])
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
        throw new ServiceGeneratorException($error->getMessage());
    }

    abstract public function generate(string $prompt): array;
    abstract protected function getEndpoint(): string;
    abstract protected function mountParams(string $prompt): array;
}
