<?php

namespace App\Imports;

use App\Enums\Platform;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports products with up to six Shopee and six TikTok Shop links each.
 *
 * Rows whose code already exists are skipped, invalid rows are reported and
 * every valid row is imported in its own transaction.
 */
class ProductsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /**
     * Number of link columns per platform in the template.
     */
    public const int LINKS_PER_PLATFORM = 6;

    /**
     * Maximum number of data rows accepted in one file.
     */
    public const int MAX_ROWS = 1000;

    /**
     * Template column prefix per platform.
     */
    private const array PLATFORM_PREFIXES = [
        'shopee' => Platform::Shopee,
        'tiktok' => Platform::TiktokShop,
    ];

    public int $importedCount = 0;

    /**
     * @var list<array{row: int, code: int}>
     */
    public array $skippedRows = [];

    /**
     * @var list<array{row: int, messages: list<string>}>
     */
    public array $failedRows = [];

    public bool $tooManyRows = false;

    /**
     * Get the template headings in column order.
     *
     * @return list<string>
     */
    public static function headings(): array
    {
        $headings = ['kode', 'nama', 'deskripsi', 'url_gambar', 'aktif'];

        foreach (array_keys(self::PLATFORM_PREFIXES) as $prefix) {
            for ($slot = 1; $slot <= self::LINKS_PER_PLATFORM; $slot++) {
                $headings[] = "{$prefix}_{$slot}";
                $headings[] = "{$prefix}_label_{$slot}";
            }
        }

        return $headings;
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $collection
     */
    public function collection(Collection $collection): void
    {
        if ($collection->count() > self::MAX_ROWS) {
            $this->tooManyRows = true;

            return;
        }

        $rows = $collection->map(fn (Collection $row): array => $this->normalizeRow($row->all()))->values();

        $existingCodes = Product::query()
            ->whereIn('code', $rows->pluck('kode')->filter(fn ($code) => is_numeric($code))->map(fn ($code) => (int) $code)->unique()->all())
            ->pluck('code')
            ->flip();

        $codesSeenInFile = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Row 1 holds the headings.

            $validator = Validator::make($row, $this->rules(), $this->messages());

            if ($validator->fails()) {
                $this->failedRows[] = ['row' => $rowNumber, 'messages' => $validator->errors()->all()];

                continue;
            }

            $code = (int) $row['kode'];

            if (isset($codesSeenInFile[$code])) {
                $this->failedRows[] = [
                    'row' => $rowNumber,
                    'messages' => ["Kode {$code} sudah dipakai di baris {$codesSeenInFile[$code]} pada file ini."],
                ];

                continue;
            }

            $codesSeenInFile[$code] = $rowNumber;

            if ($existingCodes->has($code)) {
                $this->skippedRows[] = ['row' => $rowNumber, 'code' => $code];

                continue;
            }

            DB::transaction(fn () => $this->createProduct($code, $row));

            $this->importedCount++;
        }
    }

    /**
     * Create the product and its affiliate links from a validated row.
     *
     * @param  array<string, string|null>  $row
     */
    private function createProduct(int $code, array $row): void
    {
        $product = Product::create([
            'code' => $code,
            'name' => $row['nama'],
            'description' => $row['deskripsi'],
            'image_url' => $row['url_gambar'],
            'is_active' => $this->parseActive($row['aktif']),
        ]);

        $sortOrder = 0;

        foreach (self::PLATFORM_PREFIXES as $prefix => $platform) {
            for ($slot = 1; $slot <= self::LINKS_PER_PLATFORM; $slot++) {
                $url = $row["{$prefix}_{$slot}"];

                if ($url === null) {
                    continue;
                }

                $product->affiliateLinks()->create([
                    'platform' => $platform,
                    'label' => $row["{$prefix}_label_{$slot}"],
                    'url' => $url,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }

    /**
     * Keep only the known columns, trim values and turn blanks into null.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, string|null>
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach (self::headings() as $column) {
            $value = $row[$column] ?? null;

            if (is_float($value) && floor($value) === $value) {
                $value = (int) $value; // Spreadsheet numbers such as 101.0
            }

            $value = is_scalar($value) ? trim((string) $value) : null;

            $normalized[$column] = $value === '' ? null : $value;
        }

        if ($normalized['aktif'] !== null) {
            $normalized['aktif'] = mb_strtolower($normalized['aktif']);
        }

        return $normalized;
    }

    /**
     * Interpret the "aktif" column; an empty cell means active.
     */
    private function parseActive(?string $value): bool
    {
        return $value === null || in_array($value, ['1', 'ya', 'y', 'yes', 'true', 'aktif'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $rules = [
            'kode' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'url_gambar' => ['nullable', 'url:https', 'max:2048'],
            'aktif' => ['nullable', 'in:1,0,ya,tidak,y,n,yes,no,true,false,aktif,nonaktif'],
        ];

        foreach (array_keys(self::PLATFORM_PREFIXES) as $prefix) {
            for ($slot = 1; $slot <= self::LINKS_PER_PLATFORM; $slot++) {
                $rules["{$prefix}_{$slot}"] = ['nullable', 'url:https', 'max:2048'];
                $rules["{$prefix}_label_{$slot}"] = ['nullable', 'string', 'max:100'];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'kode.required' => 'Kode produk wajib diisi.',
            'kode.integer' => 'Kode produk harus berupa angka.',
            'kode.min' => 'Kode produk minimal 1.',
            'kode.max' => 'Kode produk terlalu besar.',
            'nama.required' => 'Nama produk wajib diisi.',
            'nama.max' => 'Nama produk maksimal 255 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 5000 karakter.',
            'url_gambar.url' => 'url_gambar harus URL valid yang diawali https://.',
            'aktif.in' => 'Kolom aktif harus diisi 1/0 atau ya/tidak.',
            '*.url' => 'Kolom :attribute harus URL valid yang diawali https://.',
            '*.max' => 'Kolom :attribute terlalu panjang.',
        ];
    }
}
