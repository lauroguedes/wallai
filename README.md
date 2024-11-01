# Wallai - AI Smartphone Wallpaper Generator 🎨

Generate unique, AI-powered wallpapers perfectly sized for your smartphone using state-of-the-art image generation models through Replicate API.

## ✨ Features

- AI-powered wallpaper generation
- Real-time preview using
- Modern, responsive UI components
- Easy-to-use prompt interface
- Download generated wallpapers in high resolution
- Prompt generator for generating prompts

## 🔧 Tech Stack

- **Backend Framework:** [Laravel](https://laravel.com)
- **Frontend:** [Livewire](https://livewire.laravel.com) for real-time interactions
- **UI Components:** [MaryUI](https://mary-ui.com) / [DaisyUI](https://daisyui.com)
- **AI Integration:** [Replicate](https://replicate.com)

## 📋 Prerequisites

- PHP >= 8.2
- Laravel 11
- Node.js & NPM
- Replicate API key

## 🚀 Installation

1. Clone the repository:
```bash
git clone https://github.com/lauroguedes/wallai.git
cd wallai
```

2. Install dependencies:
```bash
composer install
```
```bash
npm install
```

3. Copy the environment file and set up your variables:
```bash
cp .env.example .env
```

5. Configure your Replicate API key in `.env`:
```
REPLICATE_API_TOKEN=your_api_key_here
```

6. Generate application key:
```bash
php artisan key:generate
```

7. Run migrations:
```bash
php artisan migrate
```

8. Build assets:
```bash
npm run build
```

9. Start the development server:
```bash
php artisan serve
```

## 🛠️ Configuration

### Replicate API Setup

1. Create an account at [replicate.com](https://replicate.com)
2. Generate an API key
3. Add the API key to your `.env` file

### Replicate API Settings

Default replicate API settings can be configured in `config/services.php` or using environment variables:

```php
return [
    'replicate' => [
        'key' => env('REPLICATE_API_KEY'),
        'image_generator_model' => env('REPLICATE_IMAGE_GENERATOR_MODEL', 'black-forest-labs/flux-schnell'),
        'text_generator_model' => env('REPLICATE_TEXT_GENERATOR_MODEL', 'meta/meta-llama-3-8b-instruct'),
        'aspect_ratio' => env('REPLICATE_ASPECT_RATIO', '9:21'),
        'output_format' => env('REPLICATE_OUTPUT_FORMAT', 'webp'),
    ],
];
```

## System Prompts Settings

Default system prompts can be configured or changed using environment variables.

```php
IMAGE_GENERATOR_SYSTEM_PROMPT=
TEXT_GENERATOR_SYSTEM_PROMPT=
```

## 🎯 Usage

1. Visit the application in your browser
2. Enter a description of the wallpaper you want to generate
3. Select your smartphone model or enter custom dimensions
4. Click "Generate" and wait for the AI to create your wallpaper
5. Download the generated wallpaper directly to your device
