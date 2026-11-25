<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#ee4d2d">

        <title>@yield('title', config('app.name'))</title>

        @yield('meta')

        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.3.5"></script>
        <style type="text/tailwindcss">
            @theme {
                --font-sans: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
                --color-shopee: #ee4d2d;
                --color-shopee-dark: #d73211;
                --color-tiktok: #161823;
            }
        </style>
    </head>
    <body class="min-h-screen bg-gray-100 font-sans text-gray-900 antialiased">
        <div class="mx-auto flex min-h-screen w-full max-w-md flex-col bg-white shadow-sm">
            <header class="border-b border-gray-100 px-4 py-3">
                <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight">
                    Belanja<span class="text-shopee">Sosmed</span>
                </a>
            </header>

            <main class="flex-1 px-4 py-5">
                @yield('content')
            </main>

            <footer class="border-t border-gray-100 px-4 py-4 text-center text-xs text-gray-500">
                Halaman ini berisi link affiliate. Kami dapat menerima komisi dari setiap pembelian
                tanpa biaya tambahan untuk Anda.
            </footer>
        </div>
    </body>
</html>
