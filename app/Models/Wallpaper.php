<?php

namespace App\Models;

use App\Enums\BackgroundStyle;
use App\Enums\DeviceType;
use Database\Factories\WallpaperFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Wallpaper extends Model
{
    /** @use HasFactory<WallpaperFactory> */
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'session_id',
        'device_type',
        'style',
        'path',
        'extension',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'device_type' => DeviceType::class,
            'style' => BackgroundStyle::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Wallpaper>  $query
     * @return Builder<Wallpaper>
     */
    public function scopeOwnedByWorkspace(Builder $query, string $workspaceKey): Builder
    {
        if (str_starts_with($workspaceKey, 'user:')) {
            return $query->where('user_id', (int) Str::after($workspaceKey, 'user:'));
        }

        return $query->where('session_id', $workspaceKey);
    }
}
