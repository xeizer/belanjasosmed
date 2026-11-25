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
        <img
            src="{{ $product->displayImageUrl() }}"
            alt="{{ $product->name }}"
            referrerpolicy="no-referrer"
            class="aspect-square w-full rounded-2xl bg-gray-100 object-cover"
        >

        <p class="mt-4 text-xs font-medium uppercase tracking-wider text-gray-500">Kode #{{ $product->code }}</p>
        <h1 class="mt-1 text-xl font-bold leading-snug">{{ $product->name }}</h1>

        @if ($product->description)
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $product->description }}</p>
        @endif

        <div class="mt-6 space-y-5">
            @foreach (\App\Enums\Platform::cases() as $platform)
                @continue(! $linksByPlatform->has($platform->value))

                <section>
                    <h2 class="mb-2 text-sm font-semibold text-gray-600">Beli di {{ $platform->label() }}</h2>

                    <div class="space-y-2">
                        @foreach ($linksByPlatform->get($platform->value) as $affiliateLink)
                            <a
                                href="{{ $isInactivePreview ? $affiliateLink->url : route('affiliate-links.redirect', ['product' => $product->code, 'affiliateLink' => $affiliateLink->id]) }}"
                                rel="sponsored nofollow noopener"
                                class="{{ $platform->buttonClasses() }} flex w-full items-center justify-center rounded-xl px-4 py-3 text-base font-semibold active:scale-[0.99]"
                            >
                                Beli di {{ $platform->label() }}
                                @if ($affiliateLink->label)
                                    <span class="ml-1 font-normal opacity-90">· {{ $affiliateLink->label }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach

            @if ($linksByPlatform->isEmpty())
                <p class="rounded-xl bg-gray-50 px-4 py-3 text-center text-sm text-gray-600">
                    Link pembelian untuk produk ini belum tersedia.
                </p>
            @endif
        </div>
    </article>
@endsection
