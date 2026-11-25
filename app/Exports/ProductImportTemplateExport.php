<?php

namespace App\Exports;

use App\Imports\ProductsImport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Blank import template with one example row.
 */
class ProductImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @return array<int, array<int, string|int>>
     */
    public function array(): array
    {
        $example = [
            'kode' => 101,
            'nama' => 'Contoh: Sepatu Sneakers Pria',
            'deskripsi' => 'Hapus baris contoh ini sebelum import.',
            'url_gambar' => 'https://contoh.com/gambar.jpg',
            'aktif' => 1,
            'shopee_1' => 'https://s.shopee.co.id/contoh1',
            'shopee_label_1' => 'Toko Resmi',
            'shopee_2' => 'https://s.shopee.co.id/contoh2',
            'shopee_label_2' => 'Termurah',
            'tiktok_1' => 'https://vt.tokopedia.com/t/contoh1',
            'tiktok_label_1' => 'Promo Live',
        ];

        return [array_map(fn (string $column) => $example[$column] ?? '', ProductsImport::headings())];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ProductsImport::headings();
    }
}
