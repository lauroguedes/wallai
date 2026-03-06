<?php

namespace App\Enums;

enum BackgroundStyle: string
{
    case MinimalGeometric = 'minimalGeometric';
    case BotanicalWatercolor = 'botanicalWatercolor';
    case AbstractFluidArt = 'abstractFluidArt';
    case VintageRetro = 'vintageRetro';
    case CyberpunkCityscape = 'cyberpunkCityscape';
    case StylizedIllustration = 'stylizedIllustration';
    case GraffitiStreetArt = 'graffitiStreetArt';
    case NaturalLandscape = 'naturalLandscape';
    case PixelArt = 'pixelArt';
    case PhotoRealist = 'photoRealist';
    case MangaAnime = 'mangaAnime';
    case Sensual = 'sensual';
    case CloseUpFace = 'closeUpFace';
    case Monuments = 'monuments';
    case Weather = 'weather';
    case Surrealism = 'surrealism';
    case Cyberpunk = 'cyberpunk';
    case Steampunk = 'steampunk';

    /**
     * Human-readable title for UI display.
     */
    public function title(): string
    {
        return match ($this) {
            self::MinimalGeometric => 'Minimal Geometric',
            self::BotanicalWatercolor => 'Botanical Watercolor',
            self::AbstractFluidArt => 'Abstract Fluid Art',
            self::VintageRetro => 'Vintage Retro',
            self::CyberpunkCityscape => 'Cyberpunk Cityscape',
            self::StylizedIllustration => 'Stylized Illustration',
            self::GraffitiStreetArt => 'Graffiti Street Art',
            self::NaturalLandscape => 'Natural Landscape',
            self::PixelArt => 'Pixel Art',
            self::PhotoRealist => 'Photo Realist',
            self::MangaAnime => 'Manga / Anime',
            self::Sensual => 'Sensual',
            self::CloseUpFace => 'Close Up Face',
            self::Monuments => 'Monuments',
            self::Weather => 'Weather',
            self::Surrealism => 'Surrealism',
            self::Cyberpunk => 'Cyberpunk',
            self::Steampunk => 'Steampunk',
        };
    }

    /**
     * Short description of the style's visual characteristics.
     */
    public function description(): string
    {
        return match ($this) {
            self::MinimalGeometric => 'Clean lines and simple shapes.',
            self::BotanicalWatercolor => 'Soft, floral, and organic textures.',
            self::AbstractFluidArt => 'Swirling, marble-like liquid patterns.',
            self::VintageRetro => 'Groovy 70s aesthetics and warm tones.',
            self::CyberpunkCityscape => 'Neon-lit futuristic urban environments.',
            self::StylizedIllustration => 'Flat, modern vector-style art.',
            self::GraffitiStreetArt => 'Bold, urban, and edgy spray-paint designs.',
            self::NaturalLandscape => 'High-definition scenic mountains and lakes.',
            self::PixelArt => 'Classic 8-bit and 16-bit video game aesthetics.',
            self::PhotoRealist => 'Ultra-detailed, lifelike textures.',
            self::MangaAnime => 'High-contrast, dynamic Japanese comic style.',
            self::Sensual => 'Soft lighting, intimate textures, and moody shadows.',
            self::CloseUpFace => 'Extreme detail focusing on features like the eyes.',
            self::Monuments => 'Dramatic, architectural photography of historic structures.',
            self::Weather => 'Intense atmospheric effects like storms and lightning.',
            self::Surrealism => 'Dreamlike, impossible compositions and melting objects.',
            self::Cyberpunk => 'Focused on futuristic technology and bionics.',
            self::Steampunk => 'Victorian-era industrialism with brass and gears.',
        };
    }

    /**
     * Representative preview image URL from local storage.
     */
    public function image(): string
    {
        $filename = match ($this) {
            self::MinimalGeometric => 'minimal_geometric.png',
            self::BotanicalWatercolor => 'botanical_watercolor.png',
            self::AbstractFluidArt => 'abstract_fluid.png',
            self::VintageRetro => 'vintage_retro.png',
            self::CyberpunkCityscape => 'cyberpunk_cityscape.png',
            self::StylizedIllustration => 'stylized_illustration.png',
            self::GraffitiStreetArt => 'graffiti_street_art.png',
            self::NaturalLandscape => 'natural_landscape.png',
            self::PixelArt => 'pixel_art.png',
            self::PhotoRealist => 'photo_realist.png',
            self::MangaAnime => 'manga_anime.png',
            self::Sensual => 'sensual.png',
            self::CloseUpFace => 'clouse_up_face.png',
            self::Monuments => 'monuments.png',
            self::Weather => 'weather.png',
            self::Surrealism => 'surrealism.png',
            self::Cyberpunk => 'cyberpunk.png',
            self::Steampunk => 'steampunk.png',
        };

        return '/storage/styles/'.$filename;
    }

    /**
     * Full system prompt instruction for this style.
     */
    public function systemPrompt(): string
    {
        return match ($this) {
            self::MinimalGeometric => 'Generate a background using only clean geometric shapes, sharp lines, and a minimal color palette. Avoid textures, gradients, and ornamental elements. Prioritize negative space and visual balance.',
            self::BotanicalWatercolor => 'Generate a background inspired by botanical watercolor paintings. Use soft, translucent washes of color, loose floral and leaf motifs, and organic textures. The palette should feel natural and delicate.',
            self::AbstractFluidArt => 'Generate a background that mimics abstract fluid art — swirling, marble-like liquid patterns with smooth color transitions and organic flow. Avoid hard edges. Use rich, blended hues.',
            self::VintageRetro => 'Generate a background with groovy 1970s retro aesthetics. Use warm earthy tones, vintage color palettes, retro typography textures, and aged grain effects. Evoke nostalgia and a psychedelic era vibe.',
            self::CyberpunkCityscape => 'Generate a background depicting a neon-lit futuristic cityscape at night. Use vivid neon colors (pink, cyan, purple) against dark backgrounds, featuring rain reflections, glowing signs, and dense urban architecture.',
            self::StylizedIllustration => 'Generate a background in a flat, modern vector illustration style. Use clean shapes, bold outlines, a limited color palette, and no photorealistic textures. The aesthetic should feel contemporary and graphic design-inspired.',
            self::GraffitiStreetArt => 'Generate a background inspired by graffiti street art. Use bold, expressive spray-paint strokes, vivid colors, layered tags, urban grit, and textured concrete or brick surfaces.',
            self::NaturalLandscape => 'Generate a high-definition background of a breathtaking natural landscape — mountains, lakes, forests, or coastal scenery. Use photorealistic rendering with dramatic lighting, rich colors, and fine detail.',
            self::PixelArt => 'Generate a background in classic pixel art style, inspired by 8-bit and 16-bit video games. Use visible pixel grids, a limited color palette, and retro game motifs such as tilemaps, sprites, and chiptune-era visual language.',
            self::PhotoRealist => 'Generate an ultra-detailed, photorealistic background. Focus on lifelike textures, precise lighting, and microscopic-level detail. Subjects can include mechanical components, natural materials, or industrial surfaces.',
            self::MangaAnime => 'Generate a background in the style of Japanese manga or anime. Use high-contrast linework, dynamic speed lines, halftone dot patterns, dramatic shadows, and expressive compositions typical of comic panels.',
            self::Sensual => 'Generate a background with a sensual atmosphere — soft, diffused lighting, intimate textures like silk or velvet, deep moody shadows, and a warm color palette. The tone should be elegant, mature, and evocative.',
            self::CloseUpFace => 'Generate a background based on an extreme close-up of a human face, focusing on a single feature such as the eyes, lips, or skin texture. Use macro-level detail, dramatic lighting, and an artistic portrait style.',
            self::Monuments => 'Generate a background featuring dramatic architectural photography of a historic monument or iconic structure. Use wide-angle composition, dramatic sky, strong perspective, and cinematic lighting to emphasize grandeur.',
            self::Weather => 'Generate a background depicting an intense weather event — stormy skies, lightning bolts, torrential rain, blizzard, or dramatic cloud formations. Capture the raw energy and atmosphere of extreme meteorological conditions.',
            self::Surrealism => 'Generate a surrealist background inspired by artists like Salvador Dali or Rene Magritte. Use dreamlike, impossible compositions — melting objects, floating figures, paradoxical landscapes — with hyperrealistic rendering of unreal scenes.',
            self::Cyberpunk => 'Generate a background focused on cyberpunk technology and bionics. Show futuristic cybernetic enhancements, neural interfaces, holographic overlays, and high-tech prosthetics in a gritty, neon-lit environment.',
            self::Steampunk => 'Generate a background in a steampunk aesthetic — Victorian-era industrialism combined with fantastical technology. Use brass gears, copper pipes, steam vents, aged leather, and warm amber tones to evoke a retro-futuristic atmosphere.',
        };
    }
}
