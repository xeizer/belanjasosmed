<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::admin.password')
            ->set('current_password', 'password')
            ->set('password', 'password-baru-123')
            ->set('password_confirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('password', '')
            ->assertDispatched('notify');

        $this->assertTrue(Hash::check('password-baru-123', $user->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::admin.password')
            ->set('current_password', 'salah')
            ->set('password', 'password-baru-123')
            ->set('password_confirmation', 'password-baru-123')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::admin.password')
            ->set('current_password', 'password')
            ->set('password', 'password-baru-123')
            ->set('password_confirmation', 'beda')
            ->call('updatePassword')
            ->assertHasErrors(['password' => 'confirmed']);
    }
}
