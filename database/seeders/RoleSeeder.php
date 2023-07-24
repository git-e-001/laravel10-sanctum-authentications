<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arrayOfRoleNames = [
            ['name' => 'super-admin', 'label' => 'Super Admin'],
            ['name' => 'seller', 'label' => 'Seller'],
            ['name' => 'subscriber', 'label' => 'Subscriber'],
        ];
        collect($arrayOfRoleNames)->map(function ($role) {
            return [
                'name' => $role['name'],
                'label' => $role['label'],
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->each(function ($role) {
            Role::query()->updateOrCreate([
                'name' => $role['name'],
                'label' => $role['label'],
                'guard_name' => 'web',
            ], $role);
        });
    }
}
