<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $arrayOfPermissionNames = [
            "viewAny user", "view user", "create user", "update user", "delete user",
            "viewAny role", "view role", "create role", "update role", "delete role",
            "viewAny subscriber", "view subscriber", "create subscriber", "update subscriber", "delete subscriber",
            "viewAny course_category", "view course_category", "create course_category", "update course_category", "delete course_category", "restore course_category", "forceDelete course_category",
            "viewAny course", "view course", "create course", "update course", "delete course", "restore course", "forceDelete course",
        ];
        collect($arrayOfPermissionNames)->each(function ($permission) {
            Permission::query()->updateOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => 'web',
                ],
                [
                    'name' => $permission,
                    'guard_name' => 'web',
                ]
            );
        });
    }
}
