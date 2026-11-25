<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a matcher for an error toast, optionally with an exact message.
     */
    private function errorToast(?string $message = null): \Closure
    {
        return fn (string $name, array $params): bool => ($params['type'] ?? null) === 'error'
            && ($message === null || $params['message'] === $message);
    }

    public function test_successful_login_flashes_welcome_message(): void
    {
        User::factory()->create(['name' => 'Budi', 'email' => 'budi@example.com']);

        Livewire::test('pages::admin.login')
            ->set('email', 'budi@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame('Selamat datang, Budi.', session('status'));
    }

    public function test_failed_login_shows_error_toast(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        Livewire::test('pages::admin.login')
            ->set('email', 'budi@example.com')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertDispatched('notify', $this->errorToast('Email atau password salah.'));
    }

    public function test_empty_login_form_shows_error_toast(): void
    {
        Livewire::test('pages::admin.login')
            ->call('login')
            ->assertHasErrors(['email', 'password'])
            ->assertDispatched('notify', $this->errorToast());
    }

    public function test_logout_shows_message_on_login_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/admin/logout')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Anda telah keluar.');

        $this->get('/admin/login')->assertSee('Anda telah keluar.');
    }

    public function test_product_form_validation_failure_shows_error_toast(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.products.form')
            ->set('code', '')
            ->call('save')
            ->assertHasErrors(['code', 'name'])
            ->assertDispatched('notify', $this->errorToast('Periksa kembali isian yang ditandai merah.'));
    }

    public function test_product_create_flashes_success_message(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.products.form')
            ->set('code', '321')
            ->set('name', 'Produk Baru')
            ->set('links', [])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Produk #321 ditambahkan.', session('status'));
    }

    public function test_toggling_a_deleted_product_shows_error_toast_instead_of_404(): void
    {
        $product = Product::factory()->create();
        $productId = $product->id;
        $product->delete();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.products.index')
            ->call('toggleActive', $productId)
            ->assertOk()
            ->assertDispatched('notify', $this->errorToast('Produk sudah tidak ada, mungkin baru saja dihapus.'));
    }

    public function test_deleting_a_deleted_product_shows_error_toast_instead_of_404(): void
    {
        $product = Product::factory()->create();
        $productId = $product->id;
        $product->delete();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.products.index')
            ->call('delete', $productId)
            ->assertOk()
            ->assertDispatched('notify', $this->errorToast());
    }

    public function test_password_validation_failure_shows_error_toast(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::admin.password')
            ->set('current_password', 'salah')
            ->set('password', 'password-baru-1')
            ->set('password_confirmation', 'password-baru-1')
            ->call('updatePassword')
            ->assertHasErrors(['current_password'])
            ->assertDispatched('notify', $this->errorToast());
    }

    public function test_user_form_validation_failure_shows_error_toast(): void
    {
        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test('pages::admin.users.form')
            ->call('save')
            ->assertHasErrors(['name', 'email', 'password'])
            ->assertDispatched('notify', $this->errorToast('Periksa kembali isian yang ditandai merah.'));
    }

    public function test_deleting_a_deleted_user_shows_error_toast_instead_of_404(): void
    {
        $user = User::factory()->create();
        $userId = $user->id;
        $user->delete();

        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test('pages::admin.users.index')
            ->call('delete', $userId)
            ->assertOk()
            ->assertDispatched('notify', $this->errorToast('User sudah tidak ada, mungkin baru saja dihapus.'));
    }

    public function test_admin_layout_turns_livewire_request_errors_into_toasts(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Livewire.interceptRequest', false)
            ->assertSee('Sesi berakhir. Muat ulang halaman lalu coba lagi.', false)
            ->assertSee('Koneksi terputus. Periksa internet Anda lalu coba lagi.', false);
    }
}
