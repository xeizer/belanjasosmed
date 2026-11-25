<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_shown_to_guests(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Masuk Admin');
    }

    public function test_guest_is_redirected_to_login_from_admin_pages(): void
    {
        $this->get('/admin/products')->assertRedirect(route('login'));
        $this->get('/admin/products/create')->assertRedirect(route('login'));
        $this->get('/admin/password')->assertRedirect(route('login'));
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        Livewire::test('pages::admin.login')
            ->set('email', 'admin@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_email_is_case_insensitive_and_trimmed(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        Livewire::test('pages::admin.login')
            ->set('email', '  Admin@Example.COM ')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.products.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        Livewire::test('pages::admin.login')
            ->set('email', 'admin@example.com')
            ->set('password', 'salah-password')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSet('password', '');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $component = Livewire::test('pages::admin.login')->set('email', 'admin@example.com');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $component->set('password', 'salah')->call('login');
        }

        $component->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
        $this->assertStringContainsString('Terlalu banyak percobaan', $component->errors()->first('email'));
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/login')
            ->assertRedirect(route('admin.products.index'));
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_cannot_be_triggered_with_get(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/logout')
            ->assertMethodNotAllowed();

        $this->assertAuthenticated();
    }
}
