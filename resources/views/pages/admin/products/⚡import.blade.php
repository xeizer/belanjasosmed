<?php

use App\Imports\ProductsImport;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts::admin')] #[Title('Import Produk')] class extends Component
{
    use WithFileUploads;

    public $file = null;

    /**
     * @var array{imported: int, skipped: list<array{row: int, code: int}>, failed: list<array{row: int, messages: list<string>}>}|null
     */
    public ?array $result = null;

    /**
     * Ensure the user may create products.
     */
    public function mount(): void
    {
        $this->authorize('create', Product::class);
    }

    /**
     * Import products from the uploaded spreadsheet.
     */
    public function import(): void
    {
        $this->authorize('create', Product::class);

        $this->validate([
            'file' => ['required', 'file', 'max:2048', 'mimes:xlsx,csv,txt', 'extensions:xlsx,csv'],
        ], [
            'file.required' => 'Pilih file Excel (.xlsx) atau CSV terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 2 MB.',
            'file.mimes' => 'File harus berformat .xlsx atau .csv.',
            'file.extensions' => 'File harus berformat .xlsx atau .csv.',
        ]);

        $import = new ProductsImport;

        try {
            Excel::import($import, $this->file);
        } catch (Throwable $e) {
            report($e);

            $this->reset('file', 'result');
            $this->dispatch('notify', message: 'File tidak dapat dibaca. Pastikan memakai template yang disediakan.', type: 'error');

            return;
        }

        $this->reset('file');

        if ($import->tooManyRows) {
            $this->result = null;
            $this->dispatch('notify', message: 'Maksimal '.ProductsImport::MAX_ROWS.' baris per file. Bagi file menjadi beberapa bagian.', type: 'error');

            return;
        }

        $this->result = [
            'imported' => $import->importedCount,
            'skipped' => $import->skippedRows,
            'failed' => $import->failedRows,
        ];

        $this->dispatch(
            'notify',
            message: "Import selesai: {$import->importedCount} berhasil, ".count($import->skippedRows).' dilewati, '.count($import->failedRows).' gagal.',
            type: $import->failedRows === [] ? 'success' : 'error',
        );
    }

    /**
     * Show an error toast when the file validation fails.
     */
    public function exception(Throwable $e, callable $stopPropagation): void
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('notify', message: collect($e->errors())->flatten()->first(), type: 'error');
        }
    }
};
?>

<div>
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.products.index') }}" class="rounded-lg px-2 py-1 text-gray-600 hover:bg-gray-200" aria-label="Kembali">&larr;</a>
        <h1 class="text-xl font-bold">Import Produk</h1>
    </div>

    <section class="space-y-3 rounded-2xl bg-white p-4 shadow-sm">
        <h2 class="font-semibold">1. Unduh template</h2>
        <p class="text-sm text-gray-600">
            Isi satu produk per baris. Kolom: <b>kode</b>, <b>nama</b>, deskripsi, url_gambar, aktif (1/0),
            lalu <b>shopee_1 … shopee_6</b> dan <b>tiktok_1 … tiktok_6</b> beserta label masing-masing.
            Link dan label boleh dikosongkan. Jika url_gambar kosong, dipakai gambar default.
        </p>
        <a
            href="{{ route('admin.products.import.template') }}"
            class="inline-block rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50"
        >Unduh template (.xlsx)</a>
    </section>

    <form wire:submit="import" class="mt-4 space-y-3 rounded-2xl bg-white p-4 shadow-sm">
        <h2 class="font-semibold">2. Upload file</h2>
        <p class="text-sm text-gray-600">
            Format .xlsx atau .csv, maksimal 2 MB dan {{ \App\Imports\ProductsImport::MAX_ROWS }} baris.
            Kode yang sudah ada akan <b>dilewati</b>; baris yang salah dilaporkan, baris lain tetap diimport.
        </p>

        <div>
            <label for="file" class="sr-only">File import</label>
            <input
                id="file"
                type="file"
                wire:model="file"
                accept=".xlsx,.csv"
                class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-white"
            >
            <p wire:loading wire:target="file" class="mt-1 text-sm text-gray-500">Mengunggah file...</p>
            @error('file') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="import, file"
            class="w-full rounded-xl bg-shopee px-4 py-3 font-semibold text-white hover:bg-shopee-dark disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="import">Import Produk</span>
            <span wire:loading wire:target="import">Mengimport...</span>
        </button>
    </form>

    @if ($result)
        <section class="mt-4 space-y-3 rounded-2xl bg-white p-4 shadow-sm" aria-live="polite">
            <h2 class="font-semibold">Hasil import</h2>

            <div class="grid grid-cols-3 gap-2 text-center text-sm">
                <div class="rounded-xl bg-green-50 p-3"><p class="text-2xl font-bold text-green-700">{{ $result['imported'] }}</p>Berhasil</div>
                <div class="rounded-xl bg-yellow-50 p-3"><p class="text-2xl font-bold text-yellow-700">{{ count($result['skipped']) }}</p>Dilewati</div>
                <div class="rounded-xl bg-red-50 p-3"><p class="text-2xl font-bold text-red-700">{{ count($result['failed']) }}</p>Gagal</div>
            </div>

            @if ($result['skipped'])
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-yellow-700">Dilewati (kode sudah ada)</h3>
                    <ul class="space-y-1 text-sm text-gray-700">
                        @foreach ($result['skipped'] as $skipped)
                            <li wire:key="skipped-{{ $skipped['row'] }}">Baris {{ $skipped['row'] }}: kode #{{ $skipped['code'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($result['failed'])
                <div>
                    <h3 class="mb-1 text-sm font-semibold text-red-700">Gagal (perbaiki lalu import ulang)</h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        @foreach ($result['failed'] as $failed)
                            <li wire:key="failed-{{ $failed['row'] }}">
                                <b>Baris {{ $failed['row'] }}:</b>
                                {{ implode(' ', $failed['messages']) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($result['imported'] > 0)
                <a href="{{ route('admin.products.index') }}" class="inline-block text-sm text-shopee underline">Lihat daftar produk</a>
            @endif
        </section>
    @endif
</div>
