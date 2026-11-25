<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Masuk')] class extends Component
{
    /**
     * Maximum failed login attempts per e-mail and IP address within one minute.
     */
    private const int MAX_ATTEMPTS = 5;

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * Attempt to authenticate the admin user.
     */
    public function login(): void
    {
        $this->email = Str::lower(trim($this->email));

        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, 60);

            $this->reset('password');

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        session()->regenerate();

        $this->redirectIntended(route('admin.products.index'));
    }

    /**
     * Get the rate limiting key for the current e-mail and IP address.
     */
    private function throttleKey(): string
    {
        return 'login:'.Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
};
?>

<div class="mx-auto mt-10 w-full max-w-sm">
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold">Masuk Admin</h1>
        <p class="mt-1 text-sm text-gray-600">Belanja<span class="text-shopee">Sosmed</span></p>
    </div>

    <form wire:submit="login" class="space-y-4 rounded-2xl bg-white p-5 shadow-sm">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input
                id="email"
                type="email"
                wire:model="email"
                autocomplete="username"
                autofocus
                required
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40"
            >
            @error('email') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                required
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40"
            >
            @error('password') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model="remember" class="rounded border-gray-300">
            Ingat saya
        </label>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="login"
            class="w-full rounded-xl bg-shopee px-4 py-3 font-semibold text-white hover:bg-shopee-dark disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses...</span>
        </button>
    </form>
</div>
