<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ isset($title) ? $title.' - ' : '' }}Admin {{ config('app.name') }}</title>

        @include('partials.favicon')

        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.3.5"></script>
        <style type="text/tailwindcss">
            @theme {
                --font-sans: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
                --color-shopee: #ee4d2d;
                --color-shopee-dark: #d73211;
                --color-tiktok: #161823;
            }
        </style>
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="min-h-screen bg-gray-100 font-sans text-gray-900 antialiased">
        <div class="mx-auto flex min-h-screen w-full max-w-3xl flex-col">
            @auth
                <header class="sticky top-0 z-10 border-b border-gray-200 bg-white">
                    <div class="flex items-center justify-between px-4 py-3">
                        <a href="{{ route('admin.products.index') }}" class="font-bold tracking-tight">
                            Admin <span class="text-shopee">BelanjaSosmed</span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100">
                                Keluar
                            </button>
                        </form>
                    </div>

                    <nav class="flex gap-1 overflow-x-auto px-2 pb-2 text-sm">
                        <a
                            href="{{ route('admin.products.index') }}"
                            @class([
                                'rounded-lg px-3 py-1.5 whitespace-nowrap',
                                'bg-gray-900 text-white' => request()->routeIs('admin.products.*'),
                                'text-gray-600 hover:bg-gray-100' => ! request()->routeIs('admin.products.*'),
                            ])
                        >Produk</a>
                        @can('viewAny', \App\Models\User::class)
                            <a
                                href="{{ route('admin.users.index') }}"
                                @class([
                                    'rounded-lg px-3 py-1.5 whitespace-nowrap',
                                    'bg-gray-900 text-white' => request()->routeIs('admin.users.*'),
                                    'text-gray-600 hover:bg-gray-100' => ! request()->routeIs('admin.users.*'),
                                ])
                            >User</a>
                        @endcan
                        <a
                            href="{{ route('admin.password.edit') }}"
                            @class([
                                'rounded-lg px-3 py-1.5 whitespace-nowrap',
                                'bg-gray-900 text-white' => request()->routeIs('admin.password.*'),
                                'text-gray-600 hover:bg-gray-100' => ! request()->routeIs('admin.password.*'),
                            ])
                        >Ubah Password</a>
                    </nav>
                </header>
            @endauth

            <main class="flex-1 px-4 py-5">
                {{ $slot }}
            </main>
        </div>

        {{-- Notifikasi: dari session flash (setelah redirect) atau event "notify" dari komponen Livewire --}}
        <div
            x-data="{
                show: false,
                message: '',
                type: 'success',
                timer: null,
                notify(detail) {
                    this.message = detail.message;
                    this.type = detail.type ?? 'success';
                    this.show = true;
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.show = false, 3500);
                },
            }"
            x-init="@if (session('status')) notify({ message: @js(session('status')), type: @js(session('status_type', 'success')) }) @endif"
            x-on:notify.window="notify($event.detail)"
            x-show="show"
            x-transition.opacity
            x-cloak
            role="status"
            aria-live="polite"
            class="fixed inset-x-4 bottom-4 z-50 mx-auto max-w-sm"
        >
            <div
                class="rounded-xl px-4 py-3 text-sm font-medium text-white shadow-lg"
                :class="type === 'error' ? 'bg-red-600' : 'bg-gray-900'"
                x-text="message"
            ></div>
        </div>

        {{-- Ubah error request Livewire (sesi habis, akses ditolak, data hilang, error server, koneksi putus) menjadi toast.
             Saat APP_DEBUG aktif, popup debug bawaan Livewire tetap ditampilkan untuk memudahkan development. --}}
        <script>
            document.addEventListener('livewire:init', () => {
                const replaceErrorModal = @js(! config('app.debug'));
                const notifyError = (message) => window.dispatchEvent(
                    new CustomEvent('notify', { detail: { message, type: 'error' } })
                );
                const messages = {
                    401: 'Sesi Anda berakhir. Silakan masuk kembali.',
                    403: 'Anda tidak memiliki akses untuk tindakan ini.',
                    404: 'Data tidak ditemukan atau sudah dihapus. Muat ulang halaman.',
                    419: 'Sesi berakhir. Muat ulang halaman lalu coba lagi.',
                    429: 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
                };

                Livewire.interceptRequest(({ onError, onFailure }) => {
                    onError(({ response, preventDefault }) => {
                        if (! replaceErrorModal) {
                            return;
                        }

                        preventDefault();
                        notifyError(messages[response.status] ?? 'Terjadi kesalahan di server. Coba lagi beberapa saat.');
                    });

                    onFailure(() => notifyError('Koneksi terputus. Periksa internet Anda lalu coba lagi.'));
                });
            });
        </script>
    </body>
</html>
