<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\SampleProductSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_seeder_creates_a_super_admin_with_a_hashed_random_password(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $superAdmin = User::where('email', SuperAdminSeeder::EMAIL)->firstOrFail();

        $this->assertSame(UserRole::SuperAdmin, $superAdmin->role);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse(Hash::check('password', $superAdmin->password));
        $this->assertTrue(Hash::isHashed($superAdmin->password));
    }

    public function test_super_admin_seeder_is_idempotent(): void
    {
        $this->seed(SuperAdminSeeder::class);
        $originalPassword = User::where('email', SuperAdminSeeder::EMAIL)->value('password');

        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, User::where('email', SuperAdminSeeder::EMAIL)->count());
        $this->assertSame($originalPassword, User::where('email', SuperAdminSeeder::EMAIL)->value('password'));
    }

    public function test_sample_product_seeder_creates_products_in_local_environment(): void
    {
        $this->app['env'] = 'local';

        $this->seed(SampleProductSeeder::class);
        $this->seed(SampleProductSeeder::class);

        $this->assertSame(5, Product::count());
        $this->assertSame(5, Product::where('code', 104)->firstOrFail()->affiliateLinks()->count());
        $this->assertFalse(Product::where('code', 105)->firstOrFail()->is_active);
    }

    public function test_sample_product_seeder_does_nothing_outside_local_environment(): void
    {
        $this->app['env'] = 'production';

        $this->seed(SampleProductSeeder::class);

        $this->assertSame(0, Product::count());
    }
}
