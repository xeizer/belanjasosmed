---
name: livewire-crud-ux
description: UX and safety patterns for Livewire CRUD and other user-triggered actions (create, update, delete, upload, import, export). Use when building or changing forms, action buttons, tables with row actions, or upload/import/export flows in Livewire.
---

# Livewire CRUD UX Patterns

Check the installed Livewire version first (composer show livewire/livewire) and follow the project's existing components and notification mechanism. Do not add toast, modal, or JS libraries. Examples below are patterns, not code to paste blindly; adapt names to the project.

## Checklist for every user-triggered action

- [ ] Loading indicator scoped to that action (wire:target), not blocking unrelated UI.
- [ ] Trigger button disabled while processing; accidental double clicks cannot fire twice.
- [ ] Server-side authorization first, then server-side validation.
- [ ] Field-level validation errors shown next to each field.
- [ ] Clear success and failure feedback through the project's existing flash/notification pattern.
- [ ] Confirmation before destructive actions.
- [ ] Form input preserved after validation failure.
- [ ] Empty, loading, and error states handled.
- [ ] Accessible labels, visible focus, understandable status text.

## Form with scoped loading and disabled submit

```blade
<form wire:submit="save">
    <label for="name">Nama</label>
    <input id="name" type="text" wire:model="name">
    @error('name') <p role="alert">{{ $message }}</p> @enderror

    <button type="submit"
            wire:loading.attr="disabled"
            wire:target="save">
        <span wire:loading.remove wire:target="save">Simpan</span>
        <span wire:loading wire:target="save">Menyimpan...</span>
    </button>
</form>
```

Set wire:target explicitly even when Livewire has default form behavior, so loading states never leak to other actions.

## Save action

```php
public function save(): void
{
    $this->authorize('create', Product::class);

    $validated = $this->validate();

    Product::create($validated);

    // success feedback using the project's existing mechanism
}
```

- Authorize before validating, so unauthorized users learn nothing about the form rules.
- Persist only the validated data.
- Use a transaction only when the action performs several dependent writes; a single create or update does not need one.

## Editing an existing record

```php
use Livewire\Attributes\Locked;

#[Locked]
public int $productId;

public function update(): void
{
    $product = Product::findOrFail($this->productId);

    $this->authorize('update', $product);

    $product->update($this->validate());
}
```

- Public properties can be tampered with from the browser. Lock ID properties (verify the Locked attribute exists in the installed Livewire version) and still re-authorize inside every action.

## Destructive action with confirmation

```blade
<button type="button"
        wire:click="delete({{ $product->id }})"
        wire:confirm="Yakin ingin menghapus data ini?"
        wire:loading.attr="disabled"
        wire:target="delete({{ $product->id }})">
    Hapus
</button>
```

```php
public function delete(int $id): void
{
    $product = Product::findOrFail($id);

    $this->authorize('delete', $product);

    $product->delete();

    // success feedback using the project's existing mechanism
}
```

- Method arguments come from the browser and can be changed. Always load the record and authorize it on the server before acting; alternatively scope the query to the current user.
- Verify that wire:confirm exists in the installed Livewire version before using it.

## Errors

- Catch expected failures and show a friendly message. Log details server-side without sensitive data; never show exception text to users.

## Duplicate protection beyond the UI

Disabling a button only protects against double clicks in one browser tab. When duplicates would cause real harm (payments, registrations, stock, numbered documents), also use:
- unique constraints in the database (the real guarantee),
- transactions with row locks (lockForUpdate) for read-then-write logic,
- firstOrCreate / updateOrCreate on a natural key, backed by a unique constraint; without the constraint two concurrent requests can still both insert.

## Tables with sorting and filtering

- Validate the sort column and direction against an explicit allowlist before passing them to orderBy; column names cannot be bound.
- Paginate results and eager load relationships shown in the table.

## Uploads, imports, exports

- Show progress or a loading state for the whole operation.
- Validate file type and size on the server. Generate the stored filename on the server; never use the client filename or extension as-is.
- Store private files on a non-public disk; serve them through an authorized route or a short-lived signed URL.
- Reject SVG uploads unless sanitized.
- Move long-running imports/exports to queued jobs if the project has queues configured, and show their status.
