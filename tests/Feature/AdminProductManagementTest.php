<?php

namespace Tests\Feature;

use App\Enums\Platform;
use App\Models\AffiliateLink;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_admin_and_super_admin_can_open_product_pages(): void
    {
        $product = Product::factory()->create();

        foreach ([$this->admin, User::factory()->superAdmin()->create()] as $user) {
            $this->actingAs($user)->get('/admin/products')->assertOk()->assertSee($product->name);
            $this->actingAs($user)->get('/admin/products/create')->assertOk();
            $this->actingAs($user)->get("/admin/products/{$product->id}/edit")->assertOk();
        }
    }

    public function test_index_lists_products_with_link_and_click_counts(): void
    {
        $product = Product::factory()->create(['name' => 'Tumbler Keren']);
        AffiliateLink::factory()->for($product)->create(['click_count' => 7]);
        AffiliateLink::factory()->for($product)->create(['click_count' => 3]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.index')
            ->assertSee('Tumbler Keren')
            ->assertSee('2 link · 10 klik');
    }

    public function test_index_can_search_by_name_and_code(): void
    {
        Product::factory()->create(['code' => 777, 'name' => 'Sepatu Lari']);
        Product::factory()->create(['code' => 888, 'name' => 'Lampu Tidur']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.index')
            ->set('search', 'sepatu')
            ->assertSee('Sepatu Lari')
            ->assertDontSee('Lampu Tidur')
            ->set('search', '888')
            ->assertSee('Lampu Tidur')
            ->assertDontSee('Sepatu Lari');
    }

    public function test_admin_can_create_product_with_multiple_links(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form')
            ->set('code', '123')
            ->set('name', 'Kemeja Linen')
            ->set('description', 'Adem dipakai')
            ->set('image_url', 'https://example.com/kemeja.jpg')
            ->set('links', [
                ['id' => null, 'platform' => 'shopee', 'label' => 'Toko A', 'url' => 'https://s.shopee.co.id/a'],
                ['id' => null, 'platform' => 'shopee', 'label' => '', 'url' => 'https://s.shopee.co.id/b'],
                ['id' => null, 'platform' => 'tiktok_shop', 'label' => 'Live', 'url' => 'https://vt.tokopedia.com/t/c'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $product = Product::where('code', 123)->firstOrFail();

        $this->assertSame('Kemeja Linen', $product->name);
        $this->assertTrue($product->is_active);
        $this->assertSame(
            [['shopee', 'Toko A', 0], ['shopee', null, 1], ['tiktok_shop', 'Live', 2]],
            $product->affiliateLinks->map(fn ($link) => [$link->platform->value, $link->label, $link->sort_order])->all(),
        );
    }

    public function test_product_form_validates_input(): void
    {
        Product::factory()->create(['code' => 123]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form')
            ->set('code', '123')
            ->set('name', '')
            ->set('image_url', 'http://example.com/gambar.jpg')
            ->set('links', [
                ['id' => null, 'platform' => 'lazada', 'label' => '', 'url' => 'http://s.shopee.co.id/a'],
                ['id' => null, 'platform' => 'shopee', 'label' => '', 'url' => ''],
            ])
            ->call('save')
            ->assertHasErrors([
                'code' => 'unique',
                'name' => 'required',
                'image_url' => 'url',
                'links.0.platform',
                'links.0.url' => 'url',
                'links.1.url' => 'required',
            ]);

        $this->assertSame(1, Product::count());
    }

    public function test_product_code_must_be_numeric(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form')
            ->set('code', 'ABC')
            ->set('name', 'Produk')
            ->call('save')
            ->assertHasErrors(['code' => 'integer']);
    }

    public function test_admin_can_update_product_and_sync_links(): void
    {
        $product = Product::factory()->create(['code' => 100, 'name' => 'Lama']);
        $keptLink = AffiliateLink::factory()->for($product)->create(['click_count' => 42, 'sort_order' => 0]);
        $removedLink = AffiliateLink::factory()->for($product)->create(['sort_order' => 1]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form', ['product' => $product])
            ->assertSet('code', '100')
            ->set('name', 'Baru')
            ->set('links', [
                ['id' => null, 'platform' => 'tiktok_shop', 'label' => 'Baru', 'url' => 'https://vt.tokopedia.com/t/new'],
                ['id' => $keptLink->id, 'platform' => 'shopee', 'label' => 'Diedit', 'url' => 'https://s.shopee.co.id/edited'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $keptLink->refresh();

        $this->assertSame('Baru', $product->name);
        $this->assertSame(2, $product->affiliateLinks()->count());
        $this->assertModelMissing($removedLink);
        $this->assertSame('https://s.shopee.co.id/edited', $keptLink->url);
        $this->assertSame(42, $keptLink->click_count);
        $this->assertSame(1, $keptLink->sort_order);
        $this->assertSame(Platform::TiktokShop, $product->affiliateLinks->first()->platform);
    }

    public function test_updating_product_can_keep_its_own_code(): void
    {
        $product = Product::factory()->create(['code' => 100]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form', ['product' => $product])
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nama Baru', $product->fresh()->name);
    }

    public function test_link_id_from_another_product_cannot_be_hijacked(): void
    {
        $product = Product::factory()->create();
        $foreignLink = AffiliateLink::factory()->create(['url' => 'https://s.shopee.co.id/foreign']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form', ['product' => $product])
            ->set('links', [
                ['id' => $foreignLink->id, 'platform' => 'shopee', 'label' => '', 'url' => 'https://s.shopee.co.id/attacker'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('https://s.shopee.co.id/foreign', $foreignLink->fresh()->url);
        $this->assertNotSame($foreignLink->product_id, $product->id);
        $this->assertSame('https://s.shopee.co.id/attacker', $product->affiliateLinks()->sole()->url);
    }

    public function test_admin_can_toggle_product_visibility(): void
    {
        $product = Product::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.index')
            ->call('toggleActive', $product->id)
            ->assertDispatched('notify');

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_admin_can_delete_product_with_its_links(): void
    {
        $product = Product::factory()->create();
        $link = AffiliateLink::factory()->for($product)->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.index')
            ->call('delete', $product->id)
            ->assertDispatched('notify');

        $this->assertModelMissing($product);
        $this->assertModelMissing($link);
    }

    public function test_guest_cannot_run_product_actions(): void
    {
        $product = Product::factory()->create();

        Livewire::test('pages::admin.products.index')
            ->call('delete', $product->id)
            ->assertForbidden();

        $this->assertModelExists($product);
    }
}
