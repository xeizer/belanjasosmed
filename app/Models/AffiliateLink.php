<?php

namespace App\Models;

use App\Enums\Platform;
use Database\Factories\AffiliateLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['platform', 'label', 'url', 'sort_order'])]
class AffiliateLink extends Model
{
    /** @use HasFactory<AffiliateLinkFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'click_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the product that owns the affiliate link.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
