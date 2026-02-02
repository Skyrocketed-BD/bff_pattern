<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id_role' => 1,
                'name'    => 'Administrator',
            ],
            [
                'id_role' => 2,
                'name'    => 'Kontraktor',
            ],
            [
                'id_role' => 3,
                'name'    => 'Akunting',
            ],
            [
                'id_role' => 4,
                'name'    => 'Kasir',
            ],
            [
                'id_role' => 5,
                'name'    => 'Data Entry',
            ]
        ];

        foreach ($roles as $key => $value) {
            DB::table('role')->insert($value);
        }
    }
}
