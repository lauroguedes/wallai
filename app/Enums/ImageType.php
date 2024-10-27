<?php

namespace App\Enums;

enum ImageType: string
{
    case Artistic = 'artistic';
    case Realistic = 'realistic';
    case Abstract  = 'abstract';

    public function name(): string
    {
        return match ($this) {
            self::Artistic => 'Artistic',
            self::Realistic => 'Realistic',
            self::Abstract => 'Abstract',
        };
    }
}
