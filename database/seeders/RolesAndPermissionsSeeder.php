<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $master = Role::create(['name' => 'master']);
        $admin = Role::create(['name' => 'admin']);
        $propietario = Role::create(['name' => 'propietario']);
        $arrendatario = Role::create(['name' => 'arrendatario']);

        // Create Master User
        $user = User::firstOrCreate([
            'email' => 'master@master.com',
        ], [
            'name' => 'Master',
            'last_name' => 'System',
            'status' => 'activo',
            'password' => bcrypt('12345678')
        ]);
        
        $user->assignRole($master);
    }
}
