<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\ImageService;

/**
 * @property int $id
 * @property string $path
 * @property int|null $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read array<string, string> $presets
 * @property-read string $type_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\ImageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Image newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Image newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Image query()
 * @method static \Illuminate\Database\Eloquent\Builder|Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Image whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Image wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Image whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Image whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Image extends Model
{
    use HasFactory;

    const TYPE_STORAGE = 1;
    const TYPE_STORAGE_TEXT = 'storage';
    const TYPE_PUBLIC = 2;
    const TYPE_PUBLIC_TEXT = 'public';
    const TYPE_S3 = 3;
    const TYPE_S3_TEXT = 's3';

    const TYPE_UNKNOWN_TEXT = 'unknown';
    protected $fillable = ['path', 'type'];

    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_STORAGE => self::TYPE_STORAGE_TEXT,
            self::TYPE_PUBLIC => self::TYPE_PUBLIC_TEXT,
            self::TYPE_S3 => self::TYPE_S3_TEXT,
            default => self::TYPE_UNKNOWN_TEXT,
        };
    }

    /**
     * Get image presets for this image
     * 
     * @return array<string, string>
     */
    public function getPresetsAttribute(): array
    {
        $imageService = app(ImageService::class)
            ->setPath($this->path)
            ->setType((string) $this->type);

        return [
            'four_small' => $imageService->setPreset('four_small')->build(),
            'actual_small' => $imageService->setPreset('actual_small')->build(),
            'small' => $imageService->setPreset('small')->build(),
            'big' => $imageService->setPreset('big')->build(),
        ];
    }

    /**
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'products_images')->withTimestamps();
    }
}
