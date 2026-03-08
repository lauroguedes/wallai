<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum BackgroundStyle: string
{
    case AbstractFluidArt = 'abstractFluidArt';
    case AbstractMacOs = 'abstractMacOs';
    case BotanicalWatercolor = 'botanicalWatercolor';
    case CloseUpFace = 'closeUpFace';
    case Cyberpunk = 'cyberpunk';
    case CyberpunkCityscape = 'cyberpunkCityscape';
    case FabricTexture = 'fabricTexture';
    case GraffitiStreetArt = 'graffitiStreetArt';
    case MacroInsects = 'macroInsects';
    case MangaAnime = 'mangaAnime';
    case MinimalGeometric = 'minimalGeometric';
    case Monuments = 'monuments';
    case NaturalLandscape = 'naturalLandscape';
    case PhotoRealist = 'photoRealist';
    case PixelArt = 'pixelArt';
    case Sensual = 'sensual';
    case Steampunk = 'steampunk';
    case StylizedIllustration = 'stylizedIllustration';
    case Surrealism = 'surrealism';
    case VintageRetro = 'vintageRetro';
    case Weather = 'weather';

    /**
     * Human-readable title for UI display.
     */
    public function title(): string
    {
        return match ($this) {
            self::AbstractFluidArt => 'Abstract Fluid Art',
            self::AbstractMacOs => 'Abstract macOS',
            self::BotanicalWatercolor => 'Botanical Watercolor',
            self::CloseUpFace => 'Close Up Face',
            self::Cyberpunk => 'Cyberpunk',
            self::CyberpunkCityscape => 'Cyberpunk Cityscape',
            self::FabricTexture => 'Fabric Texture',
            self::GraffitiStreetArt => 'Graffiti Street Art',
            self::MacroInsects => 'Macro Insects',
            self::MangaAnime => 'Manga / Anime',
            self::MinimalGeometric => 'Minimal Geometric',
            self::Monuments => 'Monuments',
            self::NaturalLandscape => 'Natural Landscape',
            self::PhotoRealist => 'Photo Realist',
            self::PixelArt => 'Pixel Art',
            self::Sensual => 'Sensual',
            self::Steampunk => 'Steampunk',
            self::StylizedIllustration => 'Stylized Illustration',
            self::Surrealism => 'Surrealism',
            self::VintageRetro => 'Vintage Retro',
            self::Weather => 'Weather',
        };
    }

    /**
     * Short description of the style's visual characteristics.
     */
    public function description(): string
    {
        return match ($this) {
            self::AbstractFluidArt => 'Swirling, marble-like liquid patterns.',
            self::AbstractMacOs => 'Smooth flowing gradients and abstract shapes inspired by macOS.',
            self::BotanicalWatercolor => 'Soft, floral, and organic textures.',
            self::CloseUpFace => 'Extreme detail focusing on features like the eyes.',
            self::Cyberpunk => 'Focused on futuristic technology and bionics.',
            self::CyberpunkCityscape => 'Neon-lit futuristic urban environments.',
            self::FabricTexture => 'Cozy knit, woven, and textile surface patterns.',
            self::GraffitiStreetArt => 'Bold, urban, and edgy spray-paint designs.',
            self::MacroInsects => 'Hyperrealistic extreme macro photography of insects.',
            self::MangaAnime => 'High-contrast, dynamic Japanese comic style.',
            self::MinimalGeometric => 'Clean lines and simple shapes.',
            self::Monuments => 'Dramatic, architectural photography of historic structures.',
            self::NaturalLandscape => 'High-definition scenic mountains and lakes.',
            self::PhotoRealist => 'Ultra-detailed, lifelike textures.',
            self::PixelArt => 'Classic 8-bit and 16-bit video game aesthetics.',
            self::Sensual => 'Soft lighting, intimate textures, and moody shadows.',
            self::Steampunk => 'Victorian-era industrialism with brass and gears.',
            self::StylizedIllustration => 'Flat, modern vector-style art.',
            self::Surrealism => 'Dreamlike, impossible compositions and melting objects.',
            self::VintageRetro => 'Groovy 70s aesthetics and warm tones.',
            self::Weather => 'Intense atmospheric effects like storms and lightning.',
        };
    }

    /**
     * Lowercase slug for download filenames (no spaces or underscores).
     */
    public function slug(): string
    {
        return Str::slug($this->title(), '');
    }

    /**
     * Representative preview image URL from local storage.
     */
    public function image(): string
    {
        $filename = match ($this) {
            self::AbstractFluidArt => 'abstract_fluid.png',
            self::AbstractMacOs => 'abstract_macos.png',
            self::BotanicalWatercolor => 'botanical_watercolor.png',
            self::CloseUpFace => 'clouse_up_face.png',
            self::Cyberpunk => 'cyberpunk.png',
            self::CyberpunkCityscape => 'cyberpunk_cityscape.png',
            self::FabricTexture => 'fabric_texture.png',
            self::GraffitiStreetArt => 'graffiti_street_art.png',
            self::MacroInsects => 'macro_insects.png',
            self::MangaAnime => 'manga_anime.png',
            self::MinimalGeometric => 'minimal_geometric.png',
            self::Monuments => 'monuments.png',
            self::NaturalLandscape => 'natural_landscape.png',
            self::PhotoRealist => 'photo_realist.png',
            self::PixelArt => 'pixel_art.png',
            self::Sensual => 'sensual.png',
            self::Steampunk => 'steampunk.png',
            self::StylizedIllustration => 'stylized_illustration.png',
            self::Surrealism => 'surrealism.png',
            self::VintageRetro => 'vintage_retro.png',
            self::Weather => 'weather.png',
        };

        return '/storage/styles/'.$filename;
    }

    /**
     * Full system prompt instruction for this style.
     */
    public function systemPrompt(): string
    {
        return match ($this) {
            self::AbstractFluidArt => 'Generate a background that mimics abstract fluid art — swirling, marble-like liquid patterns with smooth color transitions and organic flow. Avoid hard edges. Use rich, blended hues.',
            self::AbstractMacOs => 'Generate an abstract 4K wallpaper in the style of modern macOS default wallpapers. Use smooth, flowing gradients with vibrant yet harmonious colors, soft luminous shapes, gentle light diffusion, and layered translucent forms. The composition should feel serene, polished, and premium — no text, no objects, purely abstract.',
            self::BotanicalWatercolor => 'Generate a background inspired by botanical watercolor paintings. Use soft, translucent washes of color, loose floral and leaf motifs, and organic textures. The palette should feel natural and delicate.',
            self::CloseUpFace => 'Generate a background based on an extreme close-up of a human face, focusing on a single feature such as the eyes, lips, or skin texture. Use macro-level detail, dramatic lighting, and an artistic portrait style.',
            self::Cyberpunk => 'Generate a background focused on cyberpunk technology and bionics. Show futuristic cybernetic enhancements, neural interfaces, holographic overlays, and high-tech prosthetics in a gritty, neon-lit environment.',
            self::CyberpunkCityscape => 'Generate a background depicting a neon-lit futuristic cityscape at night. Use vivid neon colors (pink, cyan, purple) against dark backgrounds, featuring rain reflections, glowing signs, and dense urban architecture.',
            self::FabricTexture => 'Generate a background showcasing a rich fabric or knit texture. Capture close-up detail of woven fibers, cable-knit patterns, linen weaves, or chunky wool textures. Use warm, tactile lighting that emphasizes the three-dimensional quality of the threads, with natural color palettes and cozy, handcrafted aesthetics.',
            self::GraffitiStreetArt => 'Generate a background inspired by graffiti street art. Use bold, expressive spray-paint strokes, vivid colors, layered tags, urban grit, and textured concrete or brick surfaces.',
            self::MacroInsects => 'Generate a hyperrealistic extreme macro photograph of an insect. Capture incredible detail — compound eyes, iridescent wing membranes, textured exoskeletons, fine hairs, and dewdrops. Use professional focus stacking with a creamy bokeh background, studio-quality lighting, and scientific-grade sharpness.',
            self::MangaAnime => 'Generate a background in the style of Japanese manga or anime. Use high-contrast linework, dynamic speed lines, halftone dot patterns, dramatic shadows, and expressive compositions typical of comic panels.',
            self::MinimalGeometric => 'Generate a background using only clean geometric shapes, sharp lines, and a minimal color palette. Avoid textures, gradients, and ornamental elements. Prioritize negative space and visual balance.',
            self::Monuments => 'Generate a background featuring dramatic architectural photography of a historic monument or iconic structure. Use wide-angle composition, dramatic sky, strong perspective, and cinematic lighting to emphasize grandeur.',
            self::NaturalLandscape => 'Generate a high-definition background of a breathtaking natural landscape — mountains, lakes, forests, or coastal scenery. Use photorealistic rendering with dramatic lighting, rich colors, and fine detail.',
            self::PhotoRealist => 'Generate an ultra-detailed, photorealistic background. Focus on lifelike textures, precise lighting, and microscopic-level detail. Subjects can include mechanical components, natural materials, or industrial surfaces.',
            self::PixelArt => 'Generate a background in classic pixel art style, inspired by 8-bit and 16-bit video games. Use visible pixel grids, a limited color palette, and retro game motifs such as tilemaps, sprites, and chiptune-era visual language.',
            self::Sensual => 'Generate a background with a sensual atmosphere — soft, diffused lighting, intimate textures like silk or velvet, deep moody shadows, and a warm color palette. The tone should be elegant, mature, and evocative.',
            self::Steampunk => 'Generate a background in a steampunk aesthetic — Victorian-era industrialism combined with fantastical technology. Use brass gears, copper pipes, steam vents, aged leather, and warm amber tones to evoke a retro-futuristic atmosphere.',
            self::StylizedIllustration => 'Generate a background in a flat, modern vector illustration style. Use clean shapes, bold outlines, a limited color palette, and no photorealistic textures. The aesthetic should feel contemporary and graphic design-inspired.',
            self::Surrealism => 'Generate a surrealist background inspired by artists like Salvador Dali or Rene Magritte. Use dreamlike, impossible compositions — melting objects, floating figures, paradoxical landscapes — with hyperrealistic rendering of unreal scenes.',
            self::VintageRetro => 'Generate a background with groovy 1970s retro aesthetics. Use warm earthy tones, vintage color palettes, retro typography textures, and aged grain effects. Evoke nostalgia and a psychedelic era vibe.',
            self::Weather => 'Generate a background depicting an intense weather event — stormy skies, lightning bolts, torrential rain, blizzard, or dramatic cloud formations. Capture the raw energy and atmosphere of extreme meteorological conditions.',
        };
    }
}
