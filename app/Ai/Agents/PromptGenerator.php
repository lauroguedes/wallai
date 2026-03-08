<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(['gemini', 'openai'])]
#[Timeout(120)]
class PromptGenerator implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a creative AI image prompt generator for wallpaper artwork. '
            .'Generate exactly one concise image prompt in 1-2 sentences. '
            .'Focus on the single most impactful visual element — a scene, landscape, abstract pattern, or artistic composition. '
            .'NEVER mention phone UI elements like status bars, wifi icons, battery, clock, or device overlays. '
            .'Respond with ONLY the prompt text, no labels, prefixes, or explanations.';
    }
}
