<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu_body = [1,4,5,6];
        $counter = count($menu_body);
        for ($i=1; $i <= 4; $i++) {
            $role_access[] = [
                'id_role_access'    => $i,
                'id_menu_module'    => 1,
                'id_menu_body'      => $menu_body[$i-1],
                'id_role'           => 1,
                'action'            => 'crud',
            ];
        }

        $menu_body = [3,7,8,9,10,11,12];
        $counter = count($role_access);
        for ($i=1; $i <= count($menu_body); $i++) {
            $role_access[] = [
                'id_role_access'    => $i + $counter,
                'id_menu_module'    => 2,
                'id_menu_body'      => $menu_body[$i-1],
                'id_role'           => 1,
                'action'            => 'crud',
            ];
        }

        $menu_body = [2,13,14,15,16,17,18,19,20,21,22,23];
        $counter = count($role_access);
        for ($i=1; $i <= count($menu_body); $i++) {
            $role_access[] = [
                'id_role_access'    => $i + $counter,
                'id_menu_module'    => 3,
                'id_menu_body'      => $menu_body[$i-1],
                'id_role'           => 1,
                'action'            => 'crud',
            ];
        }

        //kontraktor
        $kontraktor = [3,7,8,9,10,11,12];
        $counter = count($role_access);
        for ($i=1; $i <= count($kontraktor); $i++) {
            $role_access[] = [
                'id_role_access'    => $i + $counter,
                'id_menu_module'    => 2,
                'id_menu_body'      => $kontraktor[$i-1],
                'id_role'           => 2,
                'action'            => 'crud',
            ];
        }

        //akunting
        $counter = count($role_access);
        $role_access[] = [
            'id_role_access'    => $counter + 1,
            'id_menu_module'    => 3,
            'id_menu_body'      => 2,
            'id_role'           => 3,
            'action'            => 'crud',
        ];
        $counter = count($role_access);
        for ($i=1; $i < 12; $i++) {
            $role_access[] = [
                'id_role_access'    => $i + $counter,
                'id_menu_module'    => 3,
                'id_menu_body'      => 8 + $i,
                'id_role'           => 3,
                'action'            => 'crud',
            ];
        }

        //kasir
        $kasir = [2,15,16,17,18,19];
        $counter = count($role_access);
        for ($i=1; $i <= count($kasir); $i++) {
            $role_access[] = [
                'id_role_access'    => $i + $counter,
                'id_menu_module'    => 3,
                'id_menu_body'      => $kasir[$i-1],
                'id_role'           => 4,
                'action'            => 'crud',
            ];
        }

        //data entry
        $data_entry = [3,7,8];
        $counter = count($role_access);
        for ($i=1; $i <= count($data_entry); $i++) {
            $role_access[] = [
                'id_role_access'    => $i + $counter,
                'id_menu_module'    => 2,
                'id_menu_body'      => $data_entry[$i-1],
                'id_role'           => 5,
                'action'            => 'crud',
            ];
        }

        foreach ($role_access as $key => $value) {
            DB::table('role_access')->insert($value);
        }
    }
}
