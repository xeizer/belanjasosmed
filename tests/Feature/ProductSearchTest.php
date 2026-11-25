<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_the_search_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('name="kode"', false)
            ->assertSee(route('products.search'), false);
    }

    public function test_existing_code_redirects_to_the_product_page(): void
    {
        Product::factory()->create(['code' => 101]);

        $this->get('/cari?kode=101')->assertRedirect(route('products.show', 101));
    }

    public function test_unknown_code_redirects_home_with_an_error(): void
    {
        $this->get('/cari?kode=555')
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['kode' => 'Produk dengan kode 555 tidak ditemukan.'], errorBag: 'search');
    }

    public function test_inactive_product_code_is_not_found(): void
    {
        Product::factory()->inactive()->create(['code' => 102]);

        $this->get('/cari?kode=102')
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('kode', errorBag: 'search');
    }

    public function test_non_numeric_code_fails_validation(): void
    {
        $this->from(route('home'))
            ->get('/cari?kode=abc')
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['kode' => 'Kode produk harus berupa angka.'], errorBag: 'search');
    }

    public function test_empty_code_fails_validation(): void
    {
        $this->from(route('home'))
            ->get('/cari')
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['kode' => 'Masukkan kode produk.'], errorBag: 'search');
    }
}
