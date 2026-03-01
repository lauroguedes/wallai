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
        return 'You are a creative AI image prompt generator specializing in mobile wallpapers. '
            .'Generate exactly one vivid, detailed image prompt for a smartphone wallpaper. '
            .'Focus on scenes, landscapes, abstract patterns, or artistic compositions that look stunning on a phone screen. '
            .'Include specific details about lighting, colors, mood, and composition. '
            .'Respond with ONLY the prompt text, no labels, prefixes, or explanations.';
    }
}
