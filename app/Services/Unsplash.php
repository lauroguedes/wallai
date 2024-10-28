<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Unsplash extends AbstractImageGenerator
{
    private string $endpoint = 'https://api.unsplash.com/photos/random';

    /**
     * @throws RequestException|\Exception
     */
    public function generate(string $prompt, string $style): ?array
    {
        return $this->httpClient($this->endpoint, config('services.unsplash.key'), [
            'orientation' => 'portrait',
            'content_filter' => 'high',
            'topics' => $this->mountPrompt($style, $prompt),
        ])->json();
    }

    protected function mountPrompt(...$args): string
    {
        return 'iPhone Wallpaper, ' . implode(',', $args);
    }

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
        $params['client_id'] = $token;

        try {
            return Http::get($url, $params)->throw();
        } catch (\Throwable $th) {
            $this->handleErrors($th);
        }
    }
}
