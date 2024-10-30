<?php

namespace App\Services;

use App\Enums\ImageType;

class ReplicateGenerateImage extends AbstractImageGenerator
{
    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public function generate(string $prompt, string $style): array
    {
        $prompt = $this->mountPrompt(
            style: $style,
            prompt: $prompt,
            prompt_style: ImageType::from($style)->prompt()
        );

        $response = $this->httpClient(
            $this->getEndpoint(),
            config('services.replicate.key'),
            $this->mountParams($prompt),
            'post'
        )
            ->throw()
            ->json();

        throw_if(
            empty($response) || !$response['output'],
            new \Exception('Invalid response')
        );

        return $this->getImage($response['id'], $response['output']);
    }

    protected function mountPrompt(...$args): string
    {
        return
            sprintf(
            config('app.image_generator_system_prompt'),
            $args['style'],
            $args['prompt'],
            $args['prompt_style'],
        );
    }

    protected function mountParams(string $prompt): array
    {
        return [
            'input' => [
                'prompt' => $prompt,
                'aspect_ratio' => config('services.replicate.aspect_ratio'),
                'output_format' => config('services.replicate.output_format'),
            ]
        ];
    }

    protected function getEndpoint(): string
    {
        return sprintf(
            'https://api.replicate.com/v1/models/%s/predictions',
            config('services.replicate.image_generator_model')
        );
    }
}
