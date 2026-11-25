<?php

use App\Actions\StoreProductImage;
use App\Enums\Platform;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin')] class extends Component
{
    use WithFileUploads;

    /**
     * Maximum number of affiliate links per product.
     */
    private const int MAX_LINKS = 20;

    public ?Product $product = null;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    /**
     * Newly selected image upload (Livewire temporary file).
     */
    public $photo = null;

    /**
     * Whether the current image should be removed on save.
     */
    public bool $removeImage = false;

    public bool $is_active = true;

    /**
     * @var list<array{id: int|null, platform: string, label: string, url: string}>
     */
    public array $links = [];

    /**
     * Prepare the form for creating a new product or editing an existing one.
     */
    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->authorize('update', $product);

            $this->product = $product;
            $this->code = (string) $product->code;
            $this->name = $product->name;
            $this->description = (string) $product->description;
            $this->is_active = $product->is_active;
            $this->links = $product->affiliateLinks->map(fn ($affiliateLink): array => [
                'id' => $affiliateLink->id,
                'platform' => $affiliateLink->platform->value,
                'label' => (string) $affiliateLink->label,
                'url' => $affiliateLink->url,
            ])->all();

            return;
        }

        $this->authorize('create', Product::class);

        $this->addLink();
    }

    /**
     * Append an empty affiliate link row.
     */
    public function addLink(string $platform = 'shopee'): void
    {
        if (count($this->links) >= self::MAX_LINKS) {
            return;
        }

        $this->links[] = [
            'id' => null,
            'platform' => Platform::tryFrom($platform)?->value ?? Platform::Shopee->value,
            'label' => '',
            'url' => '',
        ];
    }

    /**
     * Remove an affiliate link row from the form.
     */
    public function removeLink(int $index): void
    {
        unset($this->links[$index]);

        $this->links = array_values($this->links);
        $this->resetValidation();
    }

    /**
     * Move an affiliate link row one position up or down.
     */
    public function moveLink(int $index, int $direction): void
    {
        $target = $index + ($direction < 0 ? -1 : 1);

        if (! isset($this->links[$index], $this->links[$target])) {
            return;
        }

        [$this->links[$index], $this->links[$target]] = [$this->links[$target], $this->links[$index]];
        $this->resetValidation();
    }

    /**
     * A newly selected file replaces any pending removal of the current image.
     */
    public function updatedPhoto(): void
    {
        $this->removeImage = false;
    }

    /**
     * Mark the current image for removal and discard a pending upload.
     */
    public function removeCurrentImage(): void
    {
        $this->reset('photo');
        $this->removeImage = true;
    }

    /**
     * Keep the current image after all.
     */
    public function keepCurrentImage(): void
    {
        $this->removeImage = false;
    }

    /**
     * Validate and persist the product and its affiliate links.
     */
    public function save(StoreProductImage $imageStorage): void
    {
        if ($this->product) {
            $this->authorize('update', $this->product);
        } else {
            $this->authorize('create', Product::class);
        }

        $validated = $this->validate();

        $previousImagePath = $this->product?->image_path;
        $newImagePath = null;

        if ($this->photo) {
            try {
                $newImagePath = $imageStorage->handle($this->photo);
            } catch (RuntimeException) {
                $this->addError('photo', 'Gambar tidak dapat diproses. Coba file JPG/PNG/WebP lain.');
                $this->dispatch('notify', message: 'Gambar tidak dapat diproses.', type: 'error');

                return;
            }
        }

        try {
            $product = DB::transaction(fn (): Product => $this->persist($validated, $newImagePath));
        } catch (UniqueConstraintViolationException) {
            $imageStorage->delete($newImagePath);
            $this->addError('code', 'Kode produk sudah dipakai produk lain.');
            $this->dispatch('notify', message: 'Kode produk sudah dipakai produk lain.', type: 'error');

            return;
        } catch (Throwable $e) {
            $imageStorage->delete($newImagePath);

            throw $e;
        }

        if ($previousImagePath !== $product->image_path) {
            $imageStorage->delete($previousImagePath);
        }

        session()->flash('status', $this->product
            ? "Produk #{$product->code} diperbarui."
            : "Produk #{$product->code} ditambahkan.");

        $this->redirectRoute('admin.products.index');
    }

    /**
     * Show an error toast when validation fails; field messages are still rendered by Livewire.
     */
    public function exception(Throwable $e, callable $stopPropagation): void
    {
        if ($e instanceof ValidationException) {
            $this->dispatch('notify', message: 'Periksa kembali isian yang ditandai merah.', type: 'error');
        }
    }

    /**
     * Save the product and synchronise its affiliate links.
     *
     * Existing links keep their click counter; links removed from the form are
     * deleted. Link IDs are only matched against links of this product.
     *
     * A new upload replaces both the previous upload and a legacy external image URL.
     *
     * @param  array{code: int|string, name: string, description: string|null, is_active: bool, links?: list<array{id: int|null, platform: string, label: string|null, url: string}>}  $validated
     */
    private function persist(array $validated, ?string $newImagePath): Product
    {
        $product = $this->product ?? new Product;

        $product->fill([
            'code' => (int) $validated['code'],
            'name' => $validated['name'],
            'description' => ($validated['description'] ?? '') ?: null,
            'is_active' => $validated['is_active'],
        ]);

        if ($newImagePath !== null) {
            $product->image_path = $newImagePath;
            $product->image_url = null;
        } elseif ($this->removeImage) {
            $product->image_path = null;
            $product->image_url = null;
        }

        $product->save();

        $existingLinks = $product->affiliateLinks()->get()->keyBy('id');
        $keptLinkIds = [];

        foreach ($validated['links'] ?? [] as $order => $link) {
            $attributes = [
                'platform' => $link['platform'],
                'label' => $link['label'] ?: null,
                'url' => $link['url'],
                'sort_order' => $order,
            ];

            $existingLink = $link['id'] !== null ? $existingLinks->get($link['id']) : null;

            if ($existingLink) {
                $existingLink->update($attributes);
                $keptLinkIds[] = $existingLink->id;

                continue;
            }

            $keptLinkIds[] = $product->affiliateLinks()->create($attributes)->id;
        }

        $product->affiliateLinks()->whereNotIn('id', $keptLinkIds)->delete();

        return $product;
    }

    /**
     * Get the validation rules for the form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'code' => ['required', 'integer', 'min:1', 'max:4294967295', Rule::unique('products', 'code')->ignore($this->product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            'is_active' => ['boolean'],
            'links' => ['array', 'max:'.self::MAX_LINKS],
            'links.*.id' => ['nullable', 'integer'],
            'links.*.platform' => ['required', Rule::enum(Platform::class)],
            'links.*.label' => ['nullable', 'string', 'max:100'],
            'links.*.url' => ['required', 'url:https', 'max:2048'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'code.required' => 'Kode produk wajib diisi.',
            'code.integer' => 'Kode produk harus berupa angka.',
            'code.min' => 'Kode produk minimal 1.',
            'code.max' => 'Kode produk terlalu besar.',
            'code.unique' => 'Kode produk sudah dipakai produk lain.',
            'name.required' => 'Nama produk wajib diisi.',
            'name.max' => 'Nama produk maksimal 255 karakter.',
            'description.max' => 'Deskripsi maksimal 5000 karakter.',
            'photo.image' => 'File harus berupa gambar JPG, PNG, atau WebP.',
            'photo.mimes' => 'File harus berupa gambar JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran gambar maksimal 2 MB.',
            'photo.dimensions' => 'Dimensi gambar maksimal 4000×4000 piksel.',
            'photo.uploaded' => 'Gambar gagal diunggah. Coba lagi atau pilih file yang lebih kecil.',
            'links.max' => 'Maksimal '.self::MAX_LINKS.' link per produk.',
            'links.*.platform.required' => 'Pilih platform.',
            'links.*.platform.enum' => 'Platform tidak valid.',
            'links.*.label.max' => 'Label maksimal 100 karakter.',
            'links.*.url.required' => 'URL link affiliate wajib diisi.',
            'links.*.url.url' => 'URL harus valid dan diawali https://.',
            'links.*.url.max' => 'URL terlalu panjang.',
        ];
    }

    /**
     * Render the component with a dynamic page title.
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return $this->view()->title($this->product ? 'Edit Produk' : 'Tambah Produk');
    }
};
?>

<div>
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.products.index') }}" class="rounded-lg px-2 py-1 text-gray-600 hover:bg-gray-200" aria-label="Kembali">&larr;</a>
        <h1 class="text-xl font-bold">{{ $product ? 'Edit Produk #'.$product->code : 'Tambah Produk' }}</h1>
    </div>

    <form wire:submit="save" class="space-y-5">
        <section class="space-y-4 rounded-2xl bg-white p-4 shadow-sm">
            <div>
                <label for="code" class="mb-1 block text-sm font-medium">Kode produk</label>
                <input
                    id="code"
                    type="text"
                    inputmode="numeric"
                    wire:model="code"
                    placeholder="Contoh: 101"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40"
                >
                @error('code') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Nama produk</label>
                <input
                    id="name"
                    type="text"
                    wire:model="name"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40"
                >
                @error('name') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-medium">Deskripsi <span class="font-normal text-gray-500">(opsional)</span></label>
                <textarea
                    id="description"
                    rows="4"
                    wire:model="description"
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40"
                ></textarea>
                @error('description') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="photo" class="mb-1 block text-sm font-medium">Gambar produk <span class="font-normal text-gray-500">(opsional)</span></label>

                <div class="flex items-start gap-3">
                    @if ($photo && ! $errors->has('photo') && $photo->isPreviewable())
                        <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau gambar baru" class="size-24 shrink-0 rounded-xl bg-gray-100 object-cover">
                    @elseif (! $removeImage && $product?->hasImage())
                        <img src="{{ $product->displayImageUrl() }}" alt="Gambar saat ini" referrerpolicy="no-referrer" class="size-24 shrink-0 rounded-xl bg-gray-100 object-cover">
                    @else
                        <div class="flex size-24 shrink-0 items-center justify-center rounded-xl bg-gray-100 p-2 text-center text-xs text-gray-500">Gambar default</div>
                    @endif

                    <div class="min-w-0 flex-1 space-y-2 text-sm">
                        <input
                            id="photo"
                            type="file"
                            wire:model="photo"
                            accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-gray-900 file:px-3 file:py-2 file:text-white"
                        >
                        <p wire:loading wire:target="photo" class="text-gray-500">Mengunggah gambar...</p>
                        <p class="text-xs text-gray-500">JPG, PNG, atau WebP, maks 2 MB. Otomatis diperkecil ke 800px (WebP).</p>

                        @if ($photo)
                            <p class="text-xs text-gray-600">Gambar baru akan dipakai setelah disimpan.</p>
                        @elseif ($removeImage)
                            <p class="text-xs text-red-600">Gambar akan dihapus saat disimpan.</p>
                            <button type="button" wire:click="keepCurrentImage" class="text-xs font-medium text-gray-700 underline">Batal hapus</button>
                        @elseif ($product?->hasImage())
                            @if (! $product->image_path)
                                <p class="text-xs text-yellow-700">Gambar saat ini dari URL luar (bisa hilang). Upload gambar untuk menggantinya.</p>
                            @endif
                            <button
                                type="button"
                                wire:click="removeCurrentImage"
                                wire:confirm="Hapus gambar produk ini? Akan diganti gambar default setelah disimpan."
                                class="text-xs font-medium text-red-600 underline"
                            >Hapus gambar</button>
                        @endif
                    </div>
                </div>
                @error('photo') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300">
                Tampilkan di halaman publik (aktif)
            </label>
        </section>

        <section class="space-y-3 rounded-2xl bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Link affiliate</h2>
                <span class="text-xs text-gray-500">{{ count($links) }} link</span>
            </div>

            @error('links') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror

            @forelse ($links as $index => $link)
                <div wire:key="link-{{ $link['id'] ?? 'new' }}-{{ $index }}" class="space-y-2 rounded-xl border border-gray-200 p-3">
                    <div class="flex items-center gap-2">
                        <label for="links-{{ $index }}-platform" class="sr-only">Platform</label>
                        <select
                            id="links-{{ $index }}-platform"
                            wire:model="links.{{ $index }}.platform"
                            class="flex-1 rounded-lg border border-gray-300 px-2 py-2 text-sm"
                        >
                            @foreach (\App\Enums\Platform::cases() as $platform)
                                <option value="{{ $platform->value }}">{{ $platform->label() }}</option>
                            @endforeach
                        </select>

                        <button type="button" wire:click="moveLink({{ $index }}, -1)" @disabled($loop->first)
                            class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm disabled:opacity-30" aria-label="Naikkan">&uarr;</button>
                        <button type="button" wire:click="moveLink({{ $index }}, 1)" @disabled($loop->last)
                            class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm disabled:opacity-30" aria-label="Turunkan">&darr;</button>
                        <button
                            type="button"
                            wire:click="removeLink({{ $index }})"
                            @if ($link['id']) wire:confirm="Hapus link ini? Jumlah kliknya ikut hilang setelah disimpan." @endif
                            class="rounded-lg px-2 py-1.5 text-sm text-red-600 hover:bg-red-50"
                        >Hapus</button>
                    </div>
                    @error("links.{$index}.platform") <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <div>
                        <label for="links-{{ $index }}-url" class="sr-only">URL affiliate</label>
                        <input
                            id="links-{{ $index }}-url"
                            type="url"
                            wire:model="links.{{ $index }}.url"
                            placeholder="https://..."
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >
                        @error("links.{$index}.url") <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="links-{{ $index }}-label" class="sr-only">Label</label>
                        <input
                            id="links-{{ $index }}-label"
                            type="text"
                            wire:model="links.{{ $index }}.label"
                            placeholder="Label opsional, mis. Toko Resmi"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >
                        @error("links.{$index}.label") <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @empty
                <p class="rounded-xl bg-gray-50 px-3 py-3 text-center text-sm text-gray-600">
                    Belum ada link. Produk tetap bisa disimpan, halaman publik akan menampilkan "link belum tersedia".
                </p>
            @endforelse

            <div class="flex gap-2">
                <button type="button" wire:click="addLink('shopee')" wire:loading.attr="disabled" wire:target="addLink"
                    class="flex-1 rounded-lg border border-shopee px-3 py-2 text-sm font-medium text-shopee hover:bg-shopee/5 disabled:opacity-50">+ Shopee</button>
                <button type="button" wire:click="addLink('tiktok_shop')" wire:loading.attr="disabled" wire:target="addLink"
                    class="flex-1 rounded-lg border border-tiktok px-3 py-2 text-sm font-medium text-tiktok hover:bg-gray-50 disabled:opacity-50">+ TikTok Shop</button>
            </div>
        </section>

        <div class="sticky bottom-0 -mx-4 border-t border-gray-200 bg-gray-100/95 px-4 py-3">
            @if ($errors->any())
                <p role="alert" class="mb-2 text-center text-sm text-red-600">Periksa kembali isian yang ditandai merah.</p>
            @endif
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save, photo"
                class="w-full rounded-xl bg-shopee px-4 py-3 font-semibold text-white hover:bg-shopee-dark disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">Simpan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
