<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_product_shows_custom_not_found_page(): void
    {
        $this->get('/product/999')
            ->assertNotFound()
            ->assertSee('Produk tidak ditemukan')
            ->assertSee(route('home'), false)
            ->assertSee('noindex', false);
    }

    public function test_inactive_product_shows_custom_not_found_page(): void
    {
        Product::factory()->inactive()->create(['code' => 105]);

        $this->get('/product/105')
            ->assertNotFound()
            ->assertSee('Cari kode lain');
    }

    public function test_unknown_url_shows_custom_not_found_page(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Cari kode lain');
    }
}
