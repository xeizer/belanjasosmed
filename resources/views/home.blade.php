@extends('layouts.public')

@section('title', 'Cari Produk - '.config('app.name'))

@section('content')
    <div class="pt-6 text-center">
        <h1 class="text-2xl font-bold">Cari produk dari konten kami</h1>
        <p class="mt-2 text-sm text-gray-600">
            Masukkan kode produk yang disebutkan di video atau postingan.
        </p>
    </div>

    <form action="{{ route('products.search') }}" method="GET" class="mt-6 space-y-3">
        <label for="kode" class="sr-only">Kode produk</label>
        <input
            id="kode"
            name="kode"
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            autocomplete="off"
            value="{{ old('kode') }}"
            placeholder="Contoh: 101"
            required
            @class([
                'w-full rounded-xl border px-4 py-3 text-center text-lg tracking-widest focus:outline-none focus:ring-2',
                'border-red-400 focus:ring-red-300' => $errors->search->has('kode'),
                'border-gray-300 focus:ring-shopee/40' => ! $errors->search->has('kode'),
            ])
        >

        @error('kode', 'search')
            <p class="text-center text-sm text-red-600">{{ $message }}</p>
        @enderror

        <button
            type="submit"
            class="w-full rounded-xl bg-shopee px-4 py-3 text-base font-semibold text-white hover:bg-shopee-dark active:scale-[0.99]"
        >
            Lihat Produk
        </button>
    </form>
@endsection
