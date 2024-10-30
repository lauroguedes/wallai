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
        throw new \Exception($error->getMessage());
    }

    protected function getImage(string $id, array|string $url): array
    {
        $url = is_array($url) ? $url[0] : $url;

        return [
            'id' => $id,
            'url' => $url,
        ];
    }

    abstract public function generate(string $prompt, string $style): array;
    abstract protected function mountPrompt(...$args): string;
    abstract protected function getEndpoint(): string;
    abstract protected function mountParams(string $prompt): array;
}
