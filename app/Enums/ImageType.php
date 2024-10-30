<?php

namespace App\Enums;

enum ImageType: string
{
    case Artistic = 'artistic';
    case Realistic = 'realistic';
    case Abstract  = 'abstract';

    public function prompt(): string
    {
        return match ($this) {
            self::Artistic => 'Artistic style with expressive and painterly techniques, bold creative interpretation.',
            self::Realistic => 'Hyper-realistic style with natural lighting and precise details, photographic quality.',
            self::Abstract => 'Abstract style with flowing patterns and harmonious colors, non-representational design.',
        };
    }
}
