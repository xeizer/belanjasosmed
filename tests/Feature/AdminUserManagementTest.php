<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_super_admin_can_open_user_pages_and_sees_menu(): void
    {
        $admin = User::factory()->create(['name' => 'Budi Admin']);

        $this->actingAs($this->superAdmin)->get('/admin/users')->assertOk()->assertSee('Budi Admin');
        $this->actingAs($this->superAdmin)->get('/admin/users/create')->assertOk();
        $this->actingAs($this->superAdmin)->get("/admin/users/{$admin->id}/edit")->assertOk();
        $this->actingAs($this->superAdmin)->get('/admin/products')->assertSee(route('admin.users.index'), false);
    }

    public function test_admin_cannot_open_user_pages_or_see_menu(): void
    {
        $admin = User::factory()->create();
        $otherAdmin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($admin)->get("/admin/users/{$otherAdmin->id}/edit")->assertForbidden();
        $this->actingAs($admin)->get('/admin/products')->assertDontSee(route('admin.users.index'), false);
    }

    public function test_admin_cannot_run_user_actions(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test('pages::admin.users.index')
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test('pages::admin.users.form')
            ->assertForbidden();

        Livewire::actingAs($admin)
            ->test('pages::admin.users.form', ['user' => $target])
            ->assertForbidden();

        $this->assertModelExists($target);
    }

    public function test_super_admin_can_create_admin_user(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.form')
            ->set('name', 'Siti')
            ->set('email', 'Siti@Example.com')
            ->set('role', 'admin')
            ->set('password', 'rahasia-123')
            ->set('password_confirmation', 'rahasia-123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'siti@example.com')->firstOrFail();

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue(Hash::check('rahasia-123', $user->password));
    }

    public function test_super_admin_can_create_another_super_admin(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.form')
            ->set('name', 'Andi')
            ->set('email', 'andi@example.com')
            ->set('role', 'super_admin')
            ->set('password', 'rahasia-123')
            ->set('password_confirmation', 'rahasia-123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(User::where('email', 'andi@example.com')->firstOrFail()->isSuperAdmin());
    }

    public function test_create_user_validates_input(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.form')
            ->set('name', '')
            ->set('email', 'ada@example.com')
            ->set('role', 'owner')
            ->set('password', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'email' => 'unique', 'role', 'password' => 'required']);

        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.form')
            ->set('name', 'X')
            ->set('email', 'x@example.com')
            ->set('password', 'pendek')
            ->set('password_confirmation', 'beda')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_editing_without_password_keeps_the_existing_password(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.form', ['user' => $admin])
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $admin->refresh();

        $this->assertSame('Nama Baru', $admin->name);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_super_admin_can_reset_password_and_change_role(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.form', ['user' => $admin])
            ->set('role', 'super_admin')
            ->set('password', 'password-baru-1')
            ->set('password_confirmation', 'password-baru-1')
            ->call('save')
            ->assertHasNoErrors();

        $admin->refresh();

        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue(Hash::check('password-baru-1', $admin->password));
    }

    public function test_super_admin_cannot_change_own_role(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.form', ['user' => $this->superAdmin])
            ->set('role', 'admin')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($this->superAdmin->fresh()->isSuperAdmin());
    }

    public function test_super_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.index')
            ->call('delete', $admin->id)
            ->assertDispatched('notify');

        $this->assertModelMissing($admin);
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test('pages::admin.users.index')
            ->call('delete', $this->superAdmin->id)
            ->assertForbidden();

        $this->assertModelExists($this->superAdmin);
    }
}
