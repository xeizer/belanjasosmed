<?php

namespace Tests\Feature;

use App\Models\AffiliateLink;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_product_is_shown_with_its_affiliate_links(): void
    {
        $product = Product::factory()->create(['code' => 123, 'name' => 'Sepatu Lari']);
        $shopeeLink = AffiliateLink::factory()->for($product)->shopee()->create(['label' => 'Toko Resmi']);
        $tiktokLink = AffiliateLink::factory()->for($product)->tiktokShop()->create();

        $response = $this->get('/product/123');

        $response->assertOk()
            ->assertSee('Sepatu Lari')
            ->assertSee('Beli di Shopee')
            ->assertSee('Toko Resmi')
            ->assertSee('Beli di TikTok Shop')
            ->assertSee(route('affiliate-links.redirect', ['product' => 123, 'affiliateLink' => $shopeeLink->id]), false)
            ->assertSee(route('affiliate-links.redirect', ['product' => 123, 'affiliateLink' => $tiktokLink->id]), false)
            ->assertSee('rel="sponsored nofollow noopener"', false)
            ->assertDontSee($shopeeLink->url, false);
    }

    public function test_affiliate_links_are_listed_in_sort_order(): void
    {
        $product = Product::factory()->create(['code' => 200]);
        AffiliateLink::factory()->for($product)->shopee()->create(['label' => 'Link Kedua', 'sort_order' => 2]);
        AffiliateLink::factory()->for($product)->shopee()->create(['label' => 'Link Pertama', 'sort_order' => 1]);

        $this->get('/product/200')
            ->assertOk()
            ->assertSeeInOrder(['Link Pertama', 'Link Kedua']);
    }

    public function test_product_without_links_shows_unavailable_message(): void
    {
        Product::factory()->create(['code' => 300]);

        $this->get('/product/300')
            ->assertOk()
            ->assertSee('Link pembelian untuk produk ini belum tersedia.');
    }

    public function test_inactive_product_returns_not_found(): void
    {
        Product::factory()->inactive()->create(['code' => 400]);

        $this->get('/product/400')->assertNotFound();
    }

    public function test_unknown_product_code_returns_not_found(): void
    {
        $this->get('/product/999')->assertNotFound();
    }

    public function test_non_numeric_product_code_returns_not_found(): void
    {
        $this->get('/product/abc')->assertNotFound();
    }
}
