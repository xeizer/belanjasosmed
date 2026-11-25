<?php

namespace Tests\Feature;

use App\Actions\StoreProductImage;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->admin = User::factory()->create();
    }

    /**
     * Get [width, height, mime] of a stored image.
     *
     * @return array{0: int, 1: int, mime: string}
     */
    private function storedImageInfo(string $path): array
    {
        return getimagesizefromstring(Storage::disk('public')->get($path));
    }

    private function storeImageFor(Product $product, string $name = 'lama.jpg'): string
    {
        $path = app(StoreProductImage::class)->handle(UploadedFile::fake()->image($name, 100, 100));
        $product->forceFill(['image_path' => $path])->save();

        return $path;
    }

    public function test_uploaded_image_is_resized_to_800px_webp_on_create(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form')
            ->set('code', '601')
            ->set('name', 'Produk Bergambar')
            ->set('links', [])
            ->set('photo', UploadedFile::fake()->image('foto.jpg', 1600, 1200))
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('code', 601)->firstOrFail();

        $this->assertNotNull($product->image_path);
        $this->assertStringStartsWith('products/', $product->image_path);
        $this->assertStringEndsWith('.webp', $product->image_path);
        $this->assertStringNotContainsString('foto', $product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

        [$width, $height] = $info = $this->storedImageInfo($product->image_path);

        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame([800, 600], [$width, $height]);
    }

    public function test_small_image_is_not_upscaled(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form')
            ->set('code', '602')
            ->set('name', 'Kecil')
            ->set('links', [])
            ->set('photo', UploadedFile::fake()->image('kecil.png', 300, 200))
            ->call('save')
            ->assertHasNoErrors();

        [$width, $height] = $this->storedImageInfo(Product::where('code', 602)->value('image_path'));

        $this->assertSame([300, 200], [$width, $height]);
    }

    public function test_replacing_image_deletes_the_old_file(): void
    {
        $product = Product::factory()->create(['image_url' => null]);
        $oldPath = $this->storeImageFor($product);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form', ['product' => $product])
            ->set('photo', UploadedFile::fake()->image('baru.webp', 500, 500))
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();

        $this->assertNotSame($oldPath, $product->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_upload_replaces_legacy_external_image_url(): void
    {
        $product = Product::factory()->create(['image_url' => 'https://cdn.example.com/lama.jpg']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form', ['product' => $product])
            ->set('photo', UploadedFile::fake()->image('baru.jpg', 400, 400))
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();

        $this->assertNull($product->image_url);
        $this->assertNotNull($product->image_path);
    }

    public function test_saving_without_new_upload_keeps_current_image(): void
    {
        $product = Product::factory()->create(['image_url' => null]);
        $path = $this->storeImageFor($product);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form', ['product' => $product])
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($path, $product->fresh()->image_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_current_image_can_be_removed(): void
    {
        $product = Product::factory()->create(['image_url' => 'https://cdn.example.com/lama.jpg']);
        $path = $this->storeImageFor($product);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form', ['product' => $product])
            ->call('removeCurrentImage')
            ->assertSet('removeImage', true)
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();

        $this->assertNull($product->image_path);
        $this->assertNull($product->image_url);
        Storage::disk('public')->assertMissing($path);
        $this->assertStringStartsWith(Product::PLACEHOLDER_IMAGE_BASE_URL, $product->displayImageUrl());
    }

    public function test_svg_pdf_and_oversized_files_are_rejected(): void
    {
        $files = [
            UploadedFile::fake()->createWithContent('gambar.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('besar.jpg', 1000, 1000)->size(3000),
        ];

        foreach ($files as $file) {
            Livewire::actingAs($this->admin)
                ->test('pages::admin.products.form')
                ->set('code', '603')
                ->set('name', 'Ditolak')
                ->set('links', [])
                ->set('photo', $file)
                ->call('save')
                ->assertHasErrors(['photo']);
        }

        $this->assertFalse(Product::where('code', 603)->exists());
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_failed_save_does_not_leave_an_orphan_file(): void
    {
        Product::factory()->create(['code' => 604]);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.form')
            ->set('code', '604')
            ->set('name', 'Kode Bentrok')
            ->set('links', [])
            ->set('photo', UploadedFile::fake()->image('foto.jpg', 200, 200))
            ->call('save')
            ->assertHasErrors(['code']);

        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_deleting_product_deletes_its_image_file(): void
    {
        $product = Product::factory()->create();
        $path = $this->storeImageFor($product);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.products.index')
            ->call('delete', $product->id);

        $this->assertModelMissing($product);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_public_page_uses_uploaded_image_before_legacy_url(): void
    {
        $product = Product::factory()->create(['code' => 605, 'image_url' => 'https://cdn.example.com/lama.jpg']);
        $path = $this->storeImageFor($product);

        $this->get('/product/605')
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($path), false)
            ->assertDontSee('https://cdn.example.com/lama.jpg', false);
    }

    public function test_guest_cannot_upload_images(): void
    {
        Livewire::test('pages::admin.products.form')->assertForbidden();
    }
}
