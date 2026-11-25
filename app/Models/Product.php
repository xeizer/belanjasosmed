<?php

namespace App\Models;

use App\Actions\StoreProductImage;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['code', 'name', 'description', 'image_url', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'code' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Placeholder image service used when a product has no image URL.
     */
    public const string PLACEHOLDER_IMAGE_BASE_URL = 'https://placehold.co/600x600/ee4d2d/ffffff/png';

    /**
     * Remove the uploaded image file when the product is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(function (Product $product): void {
            app(StoreProductImage::class)->delete($product->image_path);
        });
    }

    /**
     * Determine whether the product has an uploaded or legacy external image.
     */
    public function hasImage(): bool
    {
        return filled($this->image_path) || filled($this->image_url);
    }

    /**
     * Get the image URL to display: the uploaded image first, then a legacy
     * external URL, falling back to a generated placeholder from the CDN.
     */
    public function displayImageUrl(): string
    {
        if (filled($this->image_path)) {
            return Storage::disk(StoreProductImage::DISK)->url($this->image_path);
        }

        if (filled($this->image_url)) {
            return $this->image_url;
        }

        return self::PLACEHOLDER_IMAGE_BASE_URL.'?'.http_build_query([
            'text' => Str::limit((string) $this->name, 40, '…'),
            'font' => 'roboto',
        ]);
    }

    /**
     * Get the affiliate links of the product, in display order.
     *
     * @return HasMany<AffiliateLink, $this>
     */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(AffiliateLink::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Scope a query to only include products visible to the public.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
