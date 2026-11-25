<?php

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Produk')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /**
     * Reset to the first page whenever the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Get the paginated products for the dashboard list.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Product::query()
            ->withCount('affiliateLinks')
            ->withSum('affiliateLinks as total_clicks', 'click_count')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('name', "%{$search}%", caseSensitive: false);

                    if (ctype_digit($search)) {
                        $query->orWhere('code', (int) $search);
                    }
                });
            })
            ->latest('id')
            ->paginate(15);
    }

    /**
     * Toggle whether the product is visible on the public page.
     */
    public function toggleActive(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product) {
            $this->notifyMissingProduct();

            return;
        }

        $this->authorize('update', $product);

        $product->update(['is_active' => ! $product->is_active]);

        $this->dispatch('notify', message: $product->is_active
            ? "Produk #{$product->code} diaktifkan."
            : "Produk #{$product->code} dinonaktifkan.");
    }

    /**
     * Delete the product together with its affiliate links.
     */
    public function delete(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product) {
            $this->notifyMissingProduct();

            return;
        }

        $this->authorize('delete', $product);

        $product->delete();

        unset($this->products);

        $this->dispatch('notify', message: "Produk #{$product->code} dihapus.");
    }

    /**
     * Tell the user the product no longer exists (e.g. deleted in another tab) and refresh the list.
     */
    private function notifyMissingProduct(): void
    {
        unset($this->products);

        $this->dispatch('notify', message: 'Produk sudah tidak ada, mungkin baru saja dihapus.', type: 'error');
    }
};
?>

<div>
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-xl font-bold">Produk</h1>
        <a
            href="{{ route('admin.products.create') }}"
            class="rounded-xl bg-shopee px-4 py-2 text-sm font-semibold text-white hover:bg-shopee-dark"
        >+ Tambah</a>
    </div>

    <div class="relative mb-4">
        <label for="search" class="sr-only">Cari produk</label>
        <input
            id="search"
            type="search"
            wire:model.live.debounce.400ms="search"
            placeholder="Cari kode atau nama produk..."
            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40"
        >
        <span wire:loading wire:target="search" class="absolute top-3 right-3 text-xs text-gray-500">Mencari...</span>
    </div>

    <div class="space-y-3">
        @forelse ($this->products as $product)
            <div wire:key="product-{{ $product->id }}" class="rounded-2xl bg-white p-4 shadow-sm">
                <div class="flex gap-3">
                    @if ($product->image_url)
                        <img
                            src="{{ $product->image_url }}"
                            alt=""
                            referrerpolicy="no-referrer"
                            loading="lazy"
                            class="size-16 shrink-0 rounded-xl bg-gray-100 object-cover"
                        >
                    @else
                        <div class="size-16 shrink-0 rounded-xl bg-gray-100"></div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-gray-500">#{{ $product->code }}</span>
                            @if ($product->is_active)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">Aktif</span>
                            @else
                                <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-600">Nonaktif</span>
                            @endif
                        </div>
                        <p class="truncate font-semibold">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $product->affiliate_links_count }} link · {{ number_format((int) $product->total_clicks, 0, ',', '.') }} klik
                        </p>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2 text-sm">
                    <a
                        href="{{ route('admin.products.edit', $product) }}"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50"
                    >Edit</a>

                    @if ($product->is_active)
                        <a
                            href="{{ route('products.show', $product->code) }}"
                            target="_blank"
                            rel="noopener"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50"
                        >Lihat</a>
                    @endif

                    <button
                        type="button"
                        wire:click="toggleActive({{ $product->id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleActive({{ $product->id }})"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="toggleActive({{ $product->id }})">
                            {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </span>
                        <span wire:loading wire:target="toggleActive({{ $product->id }})">Memproses...</span>
                    </button>

                    <button
                        type="button"
                        wire:click="delete({{ $product->id }})"
                        wire:confirm="Hapus produk #{{ $product->code }} beserta semua link-nya? Tindakan ini tidak dapat dibatalkan."
                        wire:loading.attr="disabled"
                        wire:target="delete({{ $product->id }})"
                        class="ml-auto rounded-lg px-3 py-1.5 text-red-600 hover:bg-red-50 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="delete({{ $product->id }})">Hapus</span>
                        <span wire:loading wire:target="delete({{ $product->id }})">Menghapus...</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-6 text-center text-sm text-gray-600 shadow-sm">
                @if (trim($search) !== '')
                    Tidak ada produk yang cocok dengan "{{ $search }}".
                @else
                    Belum ada produk. <a href="{{ route('admin.products.create') }}" class="text-shopee underline">Tambah produk pertama</a>.
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $this->products->links() }}
    </div>
</div>
