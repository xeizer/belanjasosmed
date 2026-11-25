<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Ubah Password')] class extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Update the authenticated user's own password.
     */
    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::min(8), 'different:current_password'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ]);

        Auth::user()->update(['password' => $this->password]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('notify', message: 'Password berhasil diubah.');
    }
};
?>

<div class="mx-auto w-full max-w-md">
    <h1 class="mb-4 text-xl font-bold">Ubah Password</h1>

    <form wire:submit="updatePassword" class="space-y-4 rounded-2xl bg-white p-4 shadow-sm">
        <div>
            <label for="current_password" class="mb-1 block text-sm font-medium">Password saat ini</label>
            <input id="current_password" type="password" wire:model="current_password" autocomplete="current-password"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40">
            @error('current_password') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password baru</label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40">
            @error('password') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Ulangi password baru</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40">
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="updatePassword"
            class="w-full rounded-xl bg-shopee px-4 py-3 font-semibold text-white hover:bg-shopee-dark disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="updatePassword">Simpan Password</span>
            <span wire:loading wire:target="updatePassword">Menyimpan...</span>
        </button>
    </form>
</div>
