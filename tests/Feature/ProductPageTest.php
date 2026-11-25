<?php

namespace Tests\Feature;

use App\Models\AffiliateLink;
use App\Models\Product;
use App\Models\User;
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

    public function test_links_of_both_platforms_are_shown_in_two_columns(): void
    {
        $product = Product::factory()->create(['code' => 210]);
        AffiliateLink::factory()->for($product)->shopee()->create();
        AffiliateLink::factory()->for($product)->tiktokShop()->create();

        $this->get('/product/210')
            ->assertOk()
            ->assertSee('mt-5 grid gap-3 grid-cols-2', false)
            ->assertSeeInOrder(['>Shopee</h2>', '>TikTok Shop</h2>'], false);
    }

    public function test_single_platform_uses_full_width_and_numbers_unlabeled_links(): void
    {
        $product = Product::factory()->create(['code' => 220]);
        AffiliateLink::factory()->for($product)->shopee()->create(['label' => null, 'sort_order' => 0]);
        AffiliateLink::factory()->for($product)->shopee()->create(['label' => null, 'sort_order' => 1]);
        AffiliateLink::factory()->for($product)->shopee()->create(['label' => 'Toko Resmi', 'sort_order' => 2]);

        $this->get('/product/220')
            ->assertOk()
            ->assertDontSee('grid-cols-2', false)
            ->assertSeeInOrder(['>Beli 1</span>', '>Beli 2</span>', '>Toko Resmi</span>'], false)
            ->assertSee('aria-label="Beli di Shopee · Beli 1"', false);
    }

    public function test_single_unlabeled_link_is_called_beli(): void
    {
        $product = Product::factory()->create(['code' => 230]);
        AffiliateLink::factory()->for($product)->tiktokShop()->create(['label' => null]);

        $this->get('/product/230')
            ->assertOk()
            ->assertSee('>Beli</span>', false)
            ->assertDontSee('>Beli 1</span>', false);
    }

    public function test_long_description_is_clamped_with_read_more_button(): void
    {
        Product::factory()->create(['code' => 240, 'description' => str_repeat('Deskripsi panjang. ', 20)]);
        Product::factory()->create(['code' => 241, 'description' => 'Pendek saja.']);

        $this->get('/product/240')
            ->assertOk()
            ->assertSee('line-clamp-3', false)
            ->assertSee('selengkapnya');

        $this->get('/product/241')
            ->assertOk()
            ->assertDontSee('selengkapnya');
    }

    public function test_product_image_links_to_full_size_image(): void
    {
        Product::factory()->create(['code' => 250, 'image_url' => 'https://example.com/besar.jpg']);

        $this->get('/product/250')
            ->assertOk()
            ->assertSee('href="https://example.com/besar.jpg"', false)
            ->assertSee('class="size-28 rounded-2xl', false);
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

    public function test_guest_does_not_see_admin_edit_button(): void
    {
        $product = Product::factory()->create(['code' => 410]);

        $this->get('/product/410')
            ->assertOk()
            ->assertDontSee('Edit produk')
            ->assertDontSee(route('admin.products.edit', $product), false);
    }

    public function test_admin_and_super_admin_see_edit_button_on_active_product(): void
    {
        $product = Product::factory()->create(['code' => 420]);

        foreach ([User::factory()->create(), User::factory()->superAdmin()->create()] as $user) {
            $this->actingAs($user)
                ->get('/product/420')
                ->assertOk()
                ->assertSee('Mode admin')
                ->assertSee('Edit produk')
                ->assertSee('href="'.route('admin.products.edit', $product).'"', false)
                ->assertDontSee('Pratinjau · Produk nonaktif');
        }
    }

    public function test_admin_can_preview_inactive_product(): void
    {
        $product = Product::factory()->inactive()->create(['code' => 430]);
        $affiliateLink = AffiliateLink::factory()->for($product)->create(['url' => 'https://s.shopee.co.id/preview']);

        $this->actingAs(User::factory()->create())
            ->get('/product/430')
            ->assertOk()
            ->assertSee('Pratinjau · Produk nonaktif')
            ->assertSee('Pengunjung melihat halaman 404.')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('href="https://s.shopee.co.id/preview"', false)
            ->assertDontSee(route('affiliate-links.redirect', ['product' => 430, 'affiliateLink' => $affiliateLink->id]), false)
            ->assertSee(route('admin.products.edit', $product), false);
    }

    public function test_inactive_product_redirect_route_stays_hidden_even_for_admin(): void
    {
        $product = Product::factory()->inactive()->create(['code' => 440]);
        $affiliateLink = AffiliateLink::factory()->for($product)->create();

        $this->actingAs(User::factory()->create())
            ->get("/product/440/go/{$affiliateLink->id}")
            ->assertNotFound();

        $this->assertSame(0, $affiliateLink->fresh()->click_count);
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
