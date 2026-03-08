<?php

namespace App\Ai\Agents;

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(['gemini'])]
#[Timeout(120)]
class ImagePromptAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public BackgroundStyle $style,
        public DeviceType $deviceType,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $styleTitle = $this->style->title();
        $styleDescription = $this->style->description();
        $stylePrompt = $this->style->systemPrompt();
        $deviceValue = $this->deviceType->value;
        $orientation = $this->deviceType->orientation();
        $deviceContext = $this->deviceType->promptContext();

        $aspectRatio = $this->deviceType === DeviceType::Mobile ? '9:16' : '16:9';
        $resolution = $this->deviceType === DeviceType::Mobile ? '2160x3840' : '3840x2160';

        return <<<INSTRUCTIONS
        You are an expert AI image prompt engineer specializing in generating highly detailed, structured prompts for image generation models. Your task is to take a user's wallpaper request and produce a comprehensive structured specification that will result in a stunning, high-fidelity image.

        STYLE CONTEXT:
        - Style: {$styleTitle}
        - Style description: {$styleDescription}
        - Style guidance: {$stylePrompt}

        DEVICE CONTEXT:
        - Device type: {$deviceValue}
        - Orientation: {$orientation}
        - Usage: {$deviceContext}

        RULES:
        1. Generate ONLY artwork specifications — never include phone UI elements such as status bars, wifi icons, battery indicators, signal bars, clock, home bar, navigation buttons, or any device overlay.
        2. The aspect_ratio MUST be "{$aspectRatio}" and the resolution MUST be "{$resolution}".
        3. Match the visual style precisely to the style context provided above.
        4. The subject description should be vivid and detailed, with at least 3 descriptive phrases.
        5. Lighting and scene should complement the chosen style.
        6. Camera settings should be realistic and appropriate for the style.
        7. The negative_prompt MUST always include: "text", "watermark", "ui elements", "phone interface", "status bar", "low quality", "blurry".
        8. Leave text_rendering content as an empty string unless the user explicitly requests text in the image.
        9. The guidance_scale should be between 7.0 and 15.0 — higher for photorealistic styles, lower for abstract/artistic styles.
        10. The meta task should be "wallpaper_generation" and thinking_level should be "high".
        INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'meta' => $schema->object([
                'model_version' => $schema->string()
                    ->description('The target image model version identifier')
                    ->required(),
                'task' => $schema->string()
                    ->description('The generation task type')
                    ->required(),
                'thinking_level' => $schema->string()
                    ->description('Level of detail in prompt engineering: low, medium, or high')
                    ->enum(['low', 'medium', 'high'])
                    ->required(),
                'consistency_id' => $schema->string()
                    ->description('Unique identifier for style consistency')
                    ->required(),
                'seed' => $schema->integer()
                    ->description('Random seed for reproducibility, 0-999999999')
                    ->required(),
            ])->required(),

            'global_settings' => $schema->object([
                'aspect_ratio' => $schema->string()
                    ->description('Image aspect ratio: 9:16 for portrait, 16:9 for landscape')
                    ->enum(['9:16', '16:9', '1:1', '4:3', '3:4'])
                    ->required(),
                'resolution' => $schema->string()
                    ->description('Target resolution, e.g. 2160x3840 or 3840x2160')
                    ->required(),
                'guidance_scale' => $schema->number()
                    ->description('How strictly the model follows the prompt, 7.0-15.0')
                    ->required(),
                'quality_mode' => $schema->string()
                    ->description('Quality level for generation')
                    ->enum(['draft', 'standard', 'high', 'ultra'])
                    ->required(),
            ])->required(),

            'subject' => $schema->object([
                'entity_type' => $schema->string()
                    ->description('Primary subject type: landscape, abstract, character, object, architecture, etc.')
                    ->required(),
                'description' => $schema->array()
                    ->items($schema->string())
                    ->description('Array of 3-10 vivid descriptive phrases for the subject')
                    ->required(),
                'materials' => $schema->object([
                    'skin' => $schema->string()
                        ->description('Skin texture or primary surface material description, use "none" if not applicable')
                        ->required(),
                    'clothing' => $schema->string()
                        ->description('Clothing or secondary covering material description, use "none" if not applicable')
                        ->required(),
                ])->required(),
                'arrangement' => $schema->string()
                    ->description('Spatial arrangement or composition of the subject')
                    ->required(),
            ])->required(),

            'scene' => $schema->object([
                'environment' => $schema->string()
                    ->description('Overall environment or setting description')
                    ->required(),
                'lighting' => $schema->object([
                    'source' => $schema->string()
                        ->description('Light source type: natural, artificial, neon, ambient, etc.')
                        ->required(),
                    'direction' => $schema->string()
                        ->description('Light direction: overhead, side, backlit, diffused, etc.')
                        ->required(),
                    'atmosphere' => $schema->string()
                        ->description('Atmospheric quality: warm, cool, dramatic, ethereal, moody, etc.')
                        ->required(),
                ])->required(),
                'objects' => $schema->array()
                    ->items($schema->string())
                    ->description('Supporting objects or elements in the scene, up to 10 items')
                    ->required(),
            ])->required(),

            'technical_camera' => $schema->object([
                'lens' => $schema->string()
                    ->description('Lens type: wide-angle, telephoto, macro, 50mm prime, etc.')
                    ->required(),
                'aperture' => $schema->string()
                    ->description('Aperture setting: f/1.4, f/2.8, f/8, etc.')
                    ->required(),
                'iso' => $schema->integer()
                    ->description('ISO sensitivity value, typically 50-12800')
                    ->required(),
                'camera_angle' => $schema->string()
                    ->description('Camera angle: eye-level, low-angle, birds-eye, dutch-angle, etc.')
                    ->required(),
            ])->required(),

            'text_rendering' => $schema->object([
                'content' => $schema->string()
                    ->description('Text to render in the image, empty string if no text needed')
                    ->required(),
                'font_style' => $schema->string()
                    ->description('Font style: serif, sans-serif, handwritten, gothic, none, etc.')
                    ->required(),
                'placement' => $schema->string()
                    ->description('Text placement: center, bottom-third, top-left, none, etc.')
                    ->required(),
            ])->required(),

            'negative_prompt' => $schema->array()
                ->items($schema->string())
                ->description('At least 5 elements to exclude from the generated image')
                ->required(),
        ];
    }
}
