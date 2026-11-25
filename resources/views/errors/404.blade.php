@extends('layouts.public')

@section('title', 'Tidak Ditemukan - '.config('app.name'))

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@section('content')
    <div class="pt-10 text-center">
        <p class="text-6xl font-bold text-shopee">404</p>
        <h1 class="mt-4 text-xl font-bold">Produk tidak ditemukan</h1>
        <p class="mt-2 text-sm text-gray-600">
            Produk mungkin sudah tidak tersedia, atau kode yang dimasukkan salah.
            Coba periksa lagi kode produk dari video atau postingan kami.
        </p>

        <a
            href="{{ route('home') }}"
            class="mt-8 block w-full rounded-xl bg-shopee px-4 py-3 text-base font-semibold text-white hover:bg-shopee-dark active:scale-[0.99]"
        >
            Cari kode lain
        </a>
    </div>
@endsection
