@extends('layouts.public')

@section('title', $product->name.' - '.config('app.name'))

@section('meta')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($product->description ?? $product->name, 155) }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($product->description ?? $product->name, 155) }}">
    <meta property="og:url" content="{{ route('products.show', $product->code) }}">
    <meta property="og:image" content="{{ $product->displayImageUrl() }}">
    @if ($isInactivePreview)
        <meta name="robots" content="noindex, nofollow">
    @endif
@endsection

@section('content')
    @if ($canManageProduct)
        <div @class([
            'mb-4 rounded-xl px-3 py-2 text-sm',
            'bg-yellow-50 text-yellow-800 ring-1 ring-yellow-200' => $isInactivePreview,
            'bg-gray-900 text-white' => ! $isInactivePreview,
        ])>
            <div class="flex items-center justify-between gap-3">
                <span class="font-medium">{{ $isInactivePreview ? 'Pratinjau · Produk nonaktif' : 'Mode admin' }}</span>
                <a
                    href="{{ route('admin.products.edit', $product) }}"
                    @class([
                        'shrink-0 rounded-lg px-3 py-1.5 font-semibold',
                        'bg-yellow-600 text-white hover:bg-yellow-700' => $isInactivePreview,
                        'bg-white text-gray-900 hover:bg-gray-100' => ! $isInactivePreview,
                    ])
                >Edit produk</a>
            </div>
            @if ($isInactivePreview)
                <p class="mt-1 text-xs">
                    Hanya terlihat oleh admin. Pengunjung melihat halaman 404.
                    Tombol beli di bawah langsung ke link affiliate tanpa menghitung klik.
                </p>
            @endif
        </div>
    @endif

    <article>
        <header class="flex gap-3">
            <a
                href="{{ $product->displayImageUrl() }}"
                target="_blank"
                rel="noopener"
                class="shrink-0"
                aria-label="Lihat gambar {{ $product->name }} ukuran penuh"
            >
                <img
                    src="{{ $product->displayImageUrl() }}"
                    alt="{{ $product->name }}"
                    referrerpolicy="no-referrer"
                    class="size-28 rounded-2xl bg-gray-100 object-cover"
                >
            </a>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Kode #{{ $product->code }}</p>
                <h1 class="mt-1 line-clamp-3 text-lg font-bold leading-snug">{{ $product->name }}</h1>
            </div>
        </header>

        @if ($product->description)
            @php($isLongDescription = mb_strlen($product->description) > 140 || substr_count($product->description, "\n") >= 3)
            <div class="mt-3">
                <p
                    id="product-description"
                    @class(['whitespace-pre-line text-sm leading-relaxed text-gray-700', 'line-clamp-3' => $isLongDescription])
                >{{ $product->description }}</p>
                @if ($isLongDescription)
                    <button
                        type="button"
                        aria-controls="product-description"
                        aria-expanded="false"
                        onclick="const text = document.getElementById('product-description'); const expanded = text.classList.toggle('line-clamp-3') === false; this.setAttribute('aria-expanded', expanded); this.textContent = expanded ? 'tutup' : 'selengkapnya';"
                        class="mt-1 text-sm font-medium text-shopee"
                    >selengkapnya</button>
                @endif
            </div>
        @endif

        @if ($linksByPlatform->isEmpty())
            <p class="mt-5 rounded-xl bg-gray-50 px-4 py-3 text-center text-sm text-gray-600">
                Link pembelian untuk produk ini belum tersedia.
            </p>
        @else
            <div @class(['mt-5 grid gap-3', 'grid-cols-2' => $linksByPlatform->count() > 1])>
                @foreach (\App\Enums\Platform::cases() as $platform)
                    @continue(! $linksByPlatform->has($platform->value))

                    @php($platformLinks = $linksByPlatform->get($platform->value))

                    <section class="min-w-0">
                        <h2 class="mb-2 text-sm font-semibold text-gray-700">{{ $platform->label() }}</h2>

                        <div class="space-y-2">
                            @foreach ($platformLinks as $affiliateLink)
                                @php($buttonText = $affiliateLink->label ?: ($platformLinks->count() > 1 ? 'Beli '.$loop->iteration : 'Beli'))
                                <a
                                    href="{{ $isInactivePreview ? $affiliateLink->url : route('affiliate-links.redirect', ['product' => $product->code, 'affiliateLink' => $affiliateLink->id]) }}"
                                    rel="sponsored nofollow noopener"
                                    aria-label="Beli di {{ $platform->label() }} · {{ $buttonText }}"
                                    title="Beli di {{ $platform->label() }} · {{ $buttonText }}"
                                    class="{{ $platform->buttonClasses() }} flex min-h-11 w-full items-center justify-center rounded-xl px-3 py-2.5 text-sm font-semibold active:scale-[0.99]"
                                ><span class="truncate">{{ $buttonText }}</span></a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </article>
@endsection
