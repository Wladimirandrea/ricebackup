<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('guests')->insertOrIgnore([
            ['name' => 'Invitado 1'],
            ['name' => 'Invitado 2'],
            ['name' => 'Invitado 3'],
        ]);
    }
}