<?php

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('User')] class extends Component
{
    use WithPagination;

    /**
     * Ensure only Super-Admins can open the page.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    /**
     * Get the paginated users.
     *
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()->orderBy('name')->paginate(20);
    }

    /**
     * Delete the given user account.
     */
    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('delete', $user);

        $user->delete();

        unset($this->users);

        $this->dispatch('notify', message: "User {$user->name} dihapus.");
    }
};
?>

<div>
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-xl font-bold">User</h1>
        <a
            href="{{ route('admin.users.create') }}"
            class="rounded-xl bg-shopee px-4 py-2 text-sm font-semibold text-white hover:bg-shopee-dark"
        >+ Tambah</a>
    </div>

    <div class="space-y-3">
        @forelse ($this->users as $user)
            <div wire:key="user-{{ $user->id }}" class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="truncate font-semibold">{{ $user->name }}</p>
                        <span @class([
                            'shrink-0 rounded-full px-2 py-0.5 text-xs',
                            'bg-shopee/10 text-shopee' => $user->isSuperAdmin(),
                            'bg-gray-200 text-gray-700' => ! $user->isSuperAdmin(),
                        ])>{{ $user->role?->label() }}</span>
                        @if ($user->is(auth()->user()))
                            <span class="shrink-0 text-xs text-gray-500">(Anda)</span>
                        @endif
                    </div>
                    <p class="truncate text-sm text-gray-600">{{ $user->email }}</p>
                </div>

                <div class="flex shrink-0 gap-2 text-sm">
                    <a
                        href="{{ route('admin.users.edit', $user) }}"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 hover:bg-gray-50"
                    >Edit</a>

                    @can('delete', $user)
                        <button
                            type="button"
                            wire:click="delete({{ $user->id }})"
                            wire:confirm="Hapus user {{ $user->name }} ({{ $user->email }})? Tindakan ini tidak dapat dibatalkan."
                            wire:loading.attr="disabled"
                            wire:target="delete({{ $user->id }})"
                            class="rounded-lg px-3 py-1.5 text-red-600 hover:bg-red-50 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="delete({{ $user->id }})">Hapus</span>
                            <span wire:loading wire:target="delete({{ $user->id }})">Menghapus...</span>
                        </button>
                    @endcan
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-white p-6 text-center text-sm text-gray-600 shadow-sm">Belum ada user.</div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $this->users->links() }}
    </div>
</div>
