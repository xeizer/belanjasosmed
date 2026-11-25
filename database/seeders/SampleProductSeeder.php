<?php

namespace Database\Seeders;

use App\Enums\Platform;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleProductSeeder extends Seeder
{
    /**
     * Seed sample products with affiliate links for local development only.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->info('SampleProductSeeder hanya berjalan di environment local, dilewati.');

            return;
        }

        foreach ($this->sampleProducts() as $sample) {
            if (Product::where('code', $sample['code'])->exists()) {
                continue;
            }

            DB::transaction(function () use ($sample): void {
                $product = Product::create([
                    'code' => $sample['code'],
                    'name' => $sample['name'],
                    'description' => $sample['description'],
                    'image_url' => 'https://picsum.photos/seed/belanjasosmed-'.$sample['code'].'/600/600',
                    'is_active' => $sample['is_active'],
                ]);

                foreach ($sample['links'] as $order => [$platform, $label]) {
                    $product->affiliateLinks()->create([
                        'platform' => $platform,
                        'label' => $label,
                        'url' => $platform === Platform::Shopee
                            ? 'https://s.shopee.co.id/sample'.$sample['code'].$order
                            : 'https://vt.tokopedia.com/t/sample'.$sample['code'].$order,
                        'sort_order' => $order,
                    ]);
                }
            });
        }
    }

    /**
     * Get the sample product definitions.
     *
     * @return list<array{code: int, name: string, description: string, is_active: bool, links: list<array{0: Platform, 1: string|null}>}>
     */
    private function sampleProducts(): array
    {
        return [
            [
                'code' => 101,
                'name' => 'Sepatu Sneakers Pria Casual',
                'description' => 'Sneakers ringan dan nyaman untuk aktivitas sehari-hari. Tersedia ukuran 39-44.',
                'is_active' => true,
                'links' => [
                    [Platform::Shopee, 'Toko Resmi'],
                    [Platform::Shopee, 'Toko Termurah'],
                    [Platform::TiktokShop, null],
                ],
            ],
            [
                'code' => 102,
                'name' => 'Tumbler Stainless 1 Liter',
                'description' => 'Menjaga minuman tetap dingin hingga 24 jam dan panas hingga 12 jam.',
                'is_active' => true,
                'links' => [
                    [Platform::TiktokShop, 'Promo Live'],
                    [Platform::TiktokShop, 'Toko Resmi'],
                    [Platform::Shopee, null],
                ],
            ],
            [
                'code' => 103,
                'name' => 'Lampu Tidur LED Aesthetic',
                'description' => 'Lampu tidur dengan 16 pilihan warna dan remote control.',
                'is_active' => true,
                'links' => [
                    [Platform::Shopee, null],
                ],
            ],
            [
                'code' => 104,
                'name' => 'Kemeja Linen Wanita Oversize',
                'description' => 'Bahan linen adem, cocok untuk kerja maupun santai.',
                'is_active' => true,
                'links' => [
                    [Platform::Shopee, 'Warna Lengkap'],
                    [Platform::Shopee, 'Size Besar'],
                    [Platform::Shopee, 'Termurah'],
                    [Platform::TiktokShop, 'Toko A'],
                    [Platform::TiktokShop, 'Toko B'],
                ],
            ],
            [
                'code' => 105,
                'name' => 'Produk Nonaktif (Contoh)',
                'description' => 'Contoh produk nonaktif, tidak tampil di halaman publik.',
                'is_active' => false,
                'links' => [
                    [Platform::Shopee, null],
                ],
            ],
        ];
    }
}
