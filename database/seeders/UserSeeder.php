<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory()
            ->count(5)
            ->create();
        foreach ($users as $user) {
            $user->assignRole('subscriber');
        }

        $super_admin1 = User::query()->create([
            'first_name' => 'Hadisur',
            'last_name' => 'Rahman',
            'email' => 'hudacse6@gmail.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $seller = User::query()->create([
            'first_name' => 'Seller',
            'last_name' => 'Khan',
            'email' => 'seller@gmail.com',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $super_admin1->assignRole('super-admin');
        $seller->assignRole('seller');
    }
}
