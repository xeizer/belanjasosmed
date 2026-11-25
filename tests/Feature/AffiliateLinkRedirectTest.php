<?php

namespace Tests\Feature;

use App\Models\AffiliateLink;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateLinkRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_to_the_affiliate_url_and_counts_the_click(): void
    {
        $product = Product::factory()->create(['code' => 101]);
        $affiliateLink = AffiliateLink::factory()->for($product)->create([
            'url' => 'https://s.shopee.co.id/abc123',
        ]);

        $this->get("/product/101/go/{$affiliateLink->id}")
            ->assertRedirect('https://s.shopee.co.id/abc123');

        $this->get("/product/101/go/{$affiliateLink->id}");

        $this->assertSame(2, $affiliateLink->fresh()->click_count);
    }

    public function test_link_belonging_to_another_product_is_not_found(): void
    {
        Product::factory()->create(['code' => 101]);
        $otherLink = AffiliateLink::factory()
            ->for(Product::factory()->state(['code' => 202]))
            ->create();

        $this->get("/product/101/go/{$otherLink->id}")->assertNotFound();

        $this->assertSame(0, $otherLink->fresh()->click_count);
    }

    public function test_link_of_inactive_product_is_not_found_and_not_counted(): void
    {
        $product = Product::factory()->inactive()->create(['code' => 101]);
        $affiliateLink = AffiliateLink::factory()->for($product)->create();

        $this->get("/product/101/go/{$affiliateLink->id}")->assertNotFound();

        $this->assertSame(0, $affiliateLink->fresh()->click_count);
    }

    public function test_redirect_route_is_rate_limited(): void
    {
        $product = Product::factory()->create(['code' => 101]);
        $affiliateLink = AffiliateLink::factory()->for($product)->create();

        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->get("/product/101/go/{$affiliateLink->id}")->assertRedirect();
        }

        $this->get("/product/101/go/{$affiliateLink->id}")->assertTooManyRequests();

        $this->assertSame(60, $affiliateLink->fresh()->click_count);
    }
}
