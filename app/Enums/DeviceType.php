<?php

namespace App\Enums;

enum DeviceType: string
{
    case Mobile = 'mobile';
    case Desktop = 'desktop';

    /**
     * Get the image orientation for this device type.
     */
    public function orientation(): string
    {
        return match ($this) {
            self::Mobile => 'portrait',
            self::Desktop => 'landscape',
        };
    }

    /**
     * Get the prompt context describing the device type for AI generation.
     */
    public function promptContext(): string
    {
        return match ($this) {
            self::Mobile => 'mobile phone wallpaper in portrait orientation',
            self::Desktop => 'desktop computer wallpaper in landscape/widescreen orientation',
        };
    }

    /**
     * Get the download filename prefix.
     */
    public function filenamePrefix(): string
    {
        return match ($this) {
            self::Mobile => 'phone_wallpaper',
            self::Desktop => 'desktop_wallpaper',
        };
    }
}
