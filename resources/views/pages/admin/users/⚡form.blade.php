<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::admin')] class extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'admin';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Prepare the form for creating a new user or editing an existing one.
     */
    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            $this->authorize('update', $user);

            $this->user = $user;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role->value;

            return;
        }

        $this->authorize('create', User::class);
    }

    /**
     * Validate and persist the user account.
     */
    public function save(): void
    {
        if ($this->user) {
            $this->authorize('update', $this->user);
        } else {
            $this->authorize('create', User::class);
        }

        $this->email = Str::lower(trim($this->email));

        $validated = $this->validate();

        $user = $this->user ?? new User;

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! $this->user || $this->canChangeRole()) {
            $user->role = UserRole::from($validated['role']);
        }

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        if (! $this->user) {
            $user->email_verified_at = now();
        }

        try {
            $user->save();
        } catch (UniqueConstraintViolationException) {
            $this->addError('email', 'Email sudah dipakai user lain.');

            return;
        }

        session()->flash('status', $this->user
            ? "User {$user->name} diperbarui."
            : "User {$user->name} ditambahkan.");

        $this->redirectRoute('admin.users.index');
    }

    /**
     * Determine whether the current user may change the role on this form.
     */
    public function canChangeRole(): bool
    {
        return ! $this->user || auth()->user()->can('changeRole', $this->user);
    }

    /**
     * Get the validation rules for the form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => [$this->user ? 'nullable' : 'required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah dipakai user lain.',
            'role.required' => 'Pilih role.',
            'role.enum' => 'Role tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    /**
     * Render the component with a dynamic page title.
     */
    public function render(): View
    {
        return $this->view()->title($this->user ? 'Edit User' : 'Tambah User');
    }
};
?>

<div class="mx-auto w-full max-w-md">
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ route('admin.users.index') }}" class="rounded-lg px-2 py-1 text-gray-600 hover:bg-gray-200" aria-label="Kembali">&larr;</a>
        <h1 class="text-xl font-bold">{{ $user ? 'Edit User' : 'Tambah User' }}</h1>
    </div>

    <form wire:submit="save" class="space-y-4 rounded-2xl bg-white p-4 shadow-sm">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium">Nama</label>
            <input id="name" type="text" wire:model="name" autocomplete="off"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40">
            @error('name') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="off"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40">
            @error('email') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="role" class="mb-1 block text-sm font-medium">Role</label>
            <select id="role" wire:model="role" @disabled(! $this->canChangeRole())
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 disabled:bg-gray-100 disabled:text-gray-500">
                @foreach (\App\Enums\UserRole::cases() as $roleOption)
                    <option value="{{ $roleOption->value }}">{{ $roleOption->label() }}</option>
                @endforeach
            </select>
            @if (! $this->canChangeRole())
                <p class="mt-1 text-xs text-gray-500">Anda tidak dapat mengubah role akun sendiri.</p>
            @endif
            @error('role') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">
                {{ $user ? 'Reset password' : 'Password' }}
                @if ($user) <span class="font-normal text-gray-500">(kosongkan jika tidak diubah)</span> @endif
            </label>
            <input id="password" type="password" wire:model="password" autocomplete="new-password"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40">
            @error('password') <p role="alert" class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Ulangi password</label>
            <input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password"
                class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-shopee/40">
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-xl bg-shopee px-4 py-3 font-semibold text-white hover:bg-shopee-dark disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="save">Simpan</span>
            <span wire:loading wire:target="save">Menyimpan...</span>
        </button>
    </form>
</div>
