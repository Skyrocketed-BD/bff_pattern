<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\operation\Cog;


class CogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cogs = [
            [
                'id_cog' => 1,
                'type'    => 'Low Grade',
                'min'     => 1.1,
                'max'     => 1.69,
            ],
            [
                'id_cog' => 2,
                'type'    => 'Medium Grade',
                'min'     => 1.7,
                'max'     => 1.89,
            ],
            [
                'id_cog' => 3,
                'type'    => 'High Grade',
                'min'     => 1.9,
                'max'     => 2.8,
            ],
        ];

        foreach ($cogs as $key => $value) {
            Cog::insert($value);
        }
    }
}
