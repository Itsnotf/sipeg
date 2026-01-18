<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'users index',
            'users create',
            'users edit',
            'users delete',
            'roles index',
            'roles create',
            'roles edit',
            'roles delete',
            'jabatans index',
            'jabatans create',
            'jabatans edit',
            'jabatans delete',
            'karyawans index',
            'karyawans create',
            'karyawans edit',
            'karyawans delete',
            'clients index',
            'clients create',
            'clients edit',
            'clients delete',
            'cashbons index',
            'cashbons create',
            'cashbons edit',
            'cashbons delete',
            'kontraks index',
            'kontraks create',
            'kontraks edit',
            'kontraks delete',
            'kontraks dokumens index',
            'kontraks dokumens create',
            'kontraks dokumens edit',
            'kontraks dokumens delete',
            'kontraks karyawans index',
            'kontraks karyawans create',
            'kontraks karyawans delete',
            'penggajians index',
            'penggajians show',
            'penggajians generate',
            'penggajians update',

        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::create(['name' => $permission]);
        }
    }
}
