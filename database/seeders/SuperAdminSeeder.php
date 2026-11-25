<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * The e-mail address of the initial Super-Admin account.
     */
    public const string EMAIL = 'superadmin@belanjasosmed.my.id';

    /**
     * Create the initial Super-Admin account when it does not exist yet.
     *
     * A random password is generated and shown once in the console so that
     * no credential is stored in the source code.
     */
    public function run(): void
    {
        if (User::where('email', self::EMAIL)->exists()) {
            $this->command?->info('Super-Admin '.self::EMAIL.' sudah ada, dilewati.');

            return;
        }

        $password = Str::password(16, symbols: false);

        $superAdmin = new User([
            'name' => 'Super Admin',
            'email' => self::EMAIL,
            'password' => $password,
        ]);
        $superAdmin->role = UserRole::SuperAdmin;
        $superAdmin->email_verified_at = now();
        $superAdmin->save();

        $this->command?->warn('Super-Admin dibuat. Catat kredensial ini, tidak akan ditampilkan lagi:');
        $this->command?->line('  Email    : '.self::EMAIL);
        $this->command?->line('  Password : '.$password);
    }
}
