<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('openai')]
class PromptGenerator implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a creative AI image prompt generator specializing in wallpaper background artwork. '
            .'Generate exactly one vivid, detailed image prompt for a background image. '
            .'Focus on scenes, landscapes, abstract patterns, or artistic compositions. '
            .'Include specific details about lighting, colors, mood, and composition. '
            .'NEVER mention phone UI elements like status bars, wifi icons, battery, clock, home bar, or any device interface overlay. '
            .'The prompt must describe only the artwork itself, not a phone screen. '
            .'Respond with ONLY the prompt text, no labels, prefixes, or explanations.';
    }
}
