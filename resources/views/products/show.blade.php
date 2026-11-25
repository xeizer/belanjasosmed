@extends('layouts.public')

@section('title', $product->name.' - '.config('app.name'))

@section('meta')
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($product->description ?? $product->name, 155) }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($product->description ?? $product->name, 155) }}">
    <meta property="og:url" content="{{ route('products.show', $product->code) }}">
    @if ($product->image_url)
        <meta property="og:image" content="{{ $product->image_url }}">
    @endif
@endsection

@section('content')
    <article>
        @if ($product->image_url)
            <img
                src="{{ $product->image_url }}"
                alt="{{ $product->name }}"
                referrerpolicy="no-referrer"
                class="aspect-square w-full rounded-2xl bg-gray-100 object-cover"
            >
        @endif

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
                                href="{{ route('affiliate-links.redirect', ['product' => $product->code, 'affiliateLink' => $affiliateLink->id]) }}"
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
