<?php

namespace Tests\Feature;

use App\Enums\Platform;
use App\Exports\ProductImportTemplateExport;
use App\Imports\ProductsImport;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    /**
     * Build a CSV upload using the template headings.
     *
     * @param  list<array<string, string|int>>  $rows
     */
    private function csvUpload(array $rows): UploadedFile
    {
        $headings = ProductsImport::headings();
        $lines = [implode(',', $headings)];

        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(
                fn (string $column) => '"'.str_replace('"', '""', (string) ($row[$column] ?? '')).'"',
                $headings,
            ));
        }

        return UploadedFile::fake()->createWithContent('produk.csv', implode("\n", $lines)."\n");
    }

    private function importAs(User $user, UploadedFile $file): Testable
    {
        return Livewire::actingAs($user)
            ->test('pages::admin.products.import')
            ->set('file', $file)
            ->call('import');
    }

    public function test_template_has_six_link_and_label_columns_per_platform(): void
    {
        $headings = ProductsImport::headings();

        $this->assertCount(29, $headings);
        $this->assertSame(['kode', 'nama', 'deskripsi', 'url_gambar', 'aktif'], array_slice($headings, 0, 5));
        $this->assertContains('shopee_6', $headings);
        $this->assertContains('shopee_label_6', $headings);
        $this->assertContains('tiktok_6', $headings);
        $this->assertContains('tiktok_label_6', $headings);
    }

    public function test_admin_can_download_the_template(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.products.import.template'))
            ->assertOk()
            ->assertDownload('template-import-produk.xlsx');
    }

    public function test_guest_cannot_open_import_page_or_download_template(): void
    {
        $this->get('/admin/products/import')->assertRedirect(route('login'));
        $this->get('/admin/products/import/template')->assertRedirect(route('login'));
    }

    public function test_it_imports_products_with_links_labels_and_order(): void
    {
        $this->importAs($this->admin, $this->csvUpload([[
            'kode' => 501,
            'nama' => 'Tas Ransel',
            'deskripsi' => 'Muat laptop 15 inci',
            'url_gambar' => 'https://example.com/tas.jpg',
            'aktif' => 'Ya',
            'shopee_1' => 'https://s.shopee.co.id/a',
            'shopee_label_1' => 'Toko Resmi',
            'shopee_6' => 'https://s.shopee.co.id/f',
            'tiktok_2' => 'https://vt.tokopedia.com/t/b',
            'tiktok_label_2' => 'Promo Live',
        ]]))
            ->assertHasNoErrors()
            ->assertSet('result.imported', 1)
            ->assertDispatched('notify');

        $product = Product::where('code', 501)->firstOrFail();

        $this->assertSame('Tas Ransel', $product->name);
        $this->assertTrue($product->is_active);
        $this->assertSame(
            [
                [Platform::Shopee, 'Toko Resmi', 'https://s.shopee.co.id/a', 0],
                [Platform::Shopee, null, 'https://s.shopee.co.id/f', 1],
                [Platform::TiktokShop, 'Promo Live', 'https://vt.tokopedia.com/t/b', 2],
            ],
            $product->affiliateLinks->map(fn ($link) => [$link->platform, $link->label, $link->url, $link->sort_order])->all(),
        );
    }

    public function test_it_imports_the_downloaded_xlsx_template(): void
    {
        $xlsx = Excel::raw(new ProductImportTemplateExport, ExcelWriter::XLSX);

        $this->importAs($this->admin, UploadedFile::fake()->createWithContent('template.xlsx', $xlsx))
            ->assertHasNoErrors()
            ->assertSet('result.imported', 1);

        $product = Product::where('code', 101)->firstOrFail();

        $this->assertSame(3, $product->affiliateLinks()->count());
    }

    public function test_existing_codes_are_skipped_and_left_untouched(): void
    {
        Product::factory()->create(['code' => 700, 'name' => 'Produk Lama']);

        $this->importAs($this->admin, $this->csvUpload([
            ['kode' => 700, 'nama' => 'Nama Baru', 'shopee_1' => 'https://s.shopee.co.id/x'],
            ['kode' => 701, 'nama' => 'Produk Baru'],
        ]))
            ->assertSet('result.imported', 1)
            ->assertSet('result.skipped', [['row' => 2, 'code' => 700]]);

        $this->assertSame('Produk Lama', Product::where('code', 700)->value('name'));
        $this->assertSame(0, Product::where('code', 700)->firstOrFail()->affiliateLinks()->count());
        $this->assertTrue(Product::where('code', 701)->exists());
    }

    public function test_invalid_rows_are_reported_while_valid_rows_are_imported(): void
    {
        $component = $this->importAs($this->admin, $this->csvUpload([
            ['kode' => 'ABC', 'nama' => 'Kode Salah'],
            ['kode' => 801, 'nama' => 'Valid'],
            ['kode' => 802, 'nama' => '', 'shopee_1' => 'http://tidak-aman.com'],
            ['kode' => 801, 'nama' => 'Duplikat Di File'],
        ]))
            ->assertSet('result.imported', 1)
            ->assertDispatched('notify', fn (string $name, array $params) => $params['type'] === 'error');

        $failed = collect($component->get('result.failed'))->keyBy('row');

        $this->assertSame([2, 4, 5], $failed->keys()->all());
        $this->assertContains('Kode produk harus berupa angka.', $failed[2]['messages']);
        $this->assertContains('Nama produk wajib diisi.', $failed[4]['messages']);
        $this->assertContains('Kolom shopee 1 harus URL valid yang diawali https://.', $failed[4]['messages']);
        $this->assertStringContainsString('sudah dipakai di baris 3', $failed[5]['messages'][0]);
        $this->assertSame(['Valid'], Product::pluck('name')->all());
    }

    public function test_inactive_flag_is_respected(): void
    {
        $this->importAs($this->admin, $this->csvUpload([
            ['kode' => 901, 'nama' => 'Nonaktif', 'aktif' => 'tidak'],
            ['kode' => 902, 'nama' => 'Nol', 'aktif' => '0'],
        ]))->assertSet('result.imported', 2);

        $this->assertSame(0, Product::where('is_active', true)->count());
    }

    public function test_file_with_too_many_rows_is_rejected(): void
    {
        $rows = array_map(fn (int $i) => ['kode' => $i, 'nama' => "Produk {$i}"], range(1, ProductsImport::MAX_ROWS + 1));

        $this->importAs($this->admin, $this->csvUpload($rows))
            ->assertSet('result', null)
            ->assertDispatched('notify', fn (string $name, array $params) => $params['type'] === 'error');

        $this->assertSame(0, Product::count());
    }

    public function test_non_spreadsheet_file_is_rejected(): void
    {
        $this->importAs($this->admin, UploadedFile::fake()->create('produk.pdf', 10, 'application/pdf'))
            ->assertHasErrors(['file'])
            ->assertDispatched('notify', fn (string $name, array $params) => $params['type'] === 'error');
    }

    public function test_product_without_image_uses_cdn_placeholder(): void
    {
        $product = Product::factory()->create(['code' => 1001, 'name' => 'Tanpa Gambar', 'image_url' => null]);

        $this->assertStringStartsWith(Product::PLACEHOLDER_IMAGE_BASE_URL.'?text=Tanpa+Gambar', $product->displayImageUrl());

        $this->get('/product/1001')
            ->assertOk()
            ->assertSee(Product::PLACEHOLDER_IMAGE_BASE_URL, false);
    }

    public function test_product_with_image_keeps_its_own_url(): void
    {
        $product = Product::factory()->create(['image_url' => 'https://example.com/a.jpg']);

        $this->assertSame('https://example.com/a.jpg', $product->displayImageUrl());
    }
}
