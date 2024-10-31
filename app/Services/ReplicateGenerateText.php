<?php

namespace App\Services;

use App\Exceptions\ServiceGeneratorException;
use Illuminate\Http\Client\RequestException;

class ReplicateGenerateText extends AbstractTextGenerator
{

    /**
     * @throws RequestException
     * @throws \Throwable
     */
    public function generate(string $prompt): array
    {
        $response = $this->httpClient(
            $this->getEndpoint(),
            config('services.replicate.key'),
            $this->mountParams($prompt),
            'post'
        )
            ->throw()
            ->json();

        throw_if(
            empty($response['output']),
            new ServiceGeneratorException('Invalid response')
        );

        return $response['output'];
    }

    protected function getEndpoint(): string
    {
        return sprintf(
            'https://api.replicate.com/v1/models/%s/predictions',
            config('services.replicate.text_generator_model')
        );
    }

    protected function mountParams(string $prompt): array
    {
        return [
            'input' => [
                'prompt' => $prompt,
                'system_prompt' => config('app.text_generator_system_prompt'),
            ]
        ];
    }
}
