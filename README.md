# WallAI - AI Wallpaper Generator

Generate unique, AI-powered wallpapers for mobile and desktop using 🍌Nano Banana 2 and the Laravel AI SDK. Choose from 18 curated styles, customize your prompt, and download high-resolution images ready to use.

https://github.com/user-attachments/assets/47a2384d-e978-48b0-bf64-95930f1fb020

## Features

- **21 Curated Styles** — Minimal Geometric, Botanical Watercolor, Abstract Fluid Art, Cyberpunk, Manga/Anime, Natural Landscape, Photo Realist, Surrealism, Steampunk, and more
- **Mobile & Desktop** — Generate wallpapers in portrait (9:16) or landscape (16:9) at up to 4K resolution
- **AI Prompt Generator** — Auto-generate creative prompts with optional context from your own text
- **Real-Time Preview** — Phone mockup for mobile, monitor mockup for desktop, with frosted glass background effect
- **Structured Prompts** — The AI agent produces detailed JSON prompts covering subject, scene, lighting, camera, and negative prompts for high-quality results
- **Queue-Based Generation** — Background processing via Laravel Horizon with dedicated mobile/desktop queues
- **Session-Based Storage** — Wallpapers are stored per session with thumbnail gallery, selection, and deletion
- **Responsive UI** — Fullscreen preview on mobile devices, drawer sidebar on tablet/desktop
- **Download & Instructions** — One-tap download with "Set as Wallpaper" instructions on mobile

## Tech Stack

- **[Laravel 12](https://laravel.com)** — Backend framework
- **[Livewire v4](https://livewire.laravel.com)** — Reactive single-file components
- **[Laravel AI SDK](https://github.com/laravel/ai)** — AI agents for prompt and image generation (🍌Nano Banana 2)
- **[Laravel Horizon](https://laravel.com/docs/horizon)** — Queue monitoring and management
- **[MaryUI](https://mary-ui.com) / [DaisyUI](https://daisyui.com)** — UI component library
- **[Tailwind CSS v4](https://tailwindcss.com)** — Utility-first styling
- **[Pest](https://pestphp.com)** — Testing framework

## Prerequisites

- PHP >= 8.4
- Node.js & NPM
- Redis (required for Horizon queues)
- [Google Gemini API key](https://ai.google.dev/)

## Installation

1. Clone and install dependencies:

```bash
git clone https://github.com/lauroguedes/wallai.git
cd wallai
composer install
npm install
```

2. Set up environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure your API key in `.env`:

```
GEMINI_API_KEY=your_gemini_api_key
```

4. Run migrations and build assets:

```bash
php artisan migrate
npm run build
```

5. Start the application:

```bash
composer run dev
```

This starts the web server, queue worker, log viewer, and Vite dev server concurrently.

## Configuration

### Queue Processing

Wallpaper generation runs on dedicated queues (`wallpapers-mobile` and `wallpapers-desktop`). Configure the number of concurrent workers in `.env`:

```
WALLPAPER_QUEUE_PROCESSES=3
```

Monitor queues via Horizon at `/horizon`.

### AI Providers

The default image and prompt provider is Google Gemini. Provider configuration lives in `config/ai.php`.

## Usage

1. Open the app in your browser
2. Pick a wallpaper style from the sidebar
3. Optionally write a description — the AI prompt generator will use it as context
4. Click the dice icon to auto-generate a prompt, or write your own
5. Hit **Generate** and wait for the result
6. Switch between **Mobile** and **Desktop** tabs to generate for different devices
7. Browse your generated wallpapers in the thumbnail gallery
8. Download directly to your device

## License

[MIT](LICENSE)

---
Please if you find this project helpful, consider giving it a ⭐ on GitHub!

Crafted by artisan ⛏️ [Lauro Guedes](https://lauroguedes.dev)
