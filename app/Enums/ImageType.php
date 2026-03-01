<?php

namespace App\Enums;

enum ImageType: string
{
    case Artistic = 'artistic';
    case Realistic = 'realistic';
    case Abstract = 'abstract';

    public function prompt(): string
    {
        return match ($this) {
            self::Artistic => 'Artistic style with expressive brushwork, painterly techniques, bold creative interpretation, rich color palette, and artistic composition inspired by modern art movements.',
            self::Realistic => 'Ultra-realistic photographic style with natural lighting, precise details, subtle depth of field, and true-to-life color reproduction. Professional DSLR quality.',
            self::Abstract => 'Abstract style with flowing geometric patterns, harmonious color gradients, mesmerizing visual rhythms, and non-representational designs that create depth and movement.',
        };
    }
}
