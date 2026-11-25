<?php

namespace Database\Factories;

use App\Enums\Platform;
use App\Models\AffiliateLink;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AffiliateLink>
 */
class AffiliateLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'platform' => Platform::Shopee,
            'label' => null,
            'url' => 'https://s.shopee.co.id/'.Str::random(10),
            'click_count' => 0,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the link points to Shopee.
     */
    public function shopee(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => Platform::Shopee,
            'url' => 'https://s.shopee.co.id/'.Str::random(10),
        ]);
    }

    /**
     * Indicate that the link points to TikTok Shop.
     */
    public function tiktokShop(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => Platform::TiktokShop,
            'url' => 'https://vt.tokopedia.com/t/'.Str::random(10),
        ]);
    }
}
