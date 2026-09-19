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
            ['name' => 'Carolina'],
            ['name' => 'Pedro'],
            ['name' => 'Lenin'],
            ['name' => 'Justo'],
            ['name' => 'Chani'],
            ['name' => 'Marly'],
            ['name' => 'Enner'],
            ['name' => 'Astrid'],
            ['name' => 'Silvia'],
            ['name' => 'Gloria'],
            ['name' => 'Monica'],
            ['name' => 'Selene'],
            ['name' => 'Mary'],
            ['name' => 'Irma'],
            ['name' => 'Alexander'],
            ['name' => 'Paola'],
            ['name' => 'Michelle'],
            ['name' => 'Angela'],
            ['name' => 'Becca'],
            ['name' => 'Eddy'],
            ['name' => 'Sandra'],
            ['name' => 'Georgina'],
            ['name' => 'Isabel'],
            ['name' => 'Abuelita'],
            ['name' => 'Mama'],
            ['name' => 'brithany'],
            ['name' => 'wladimir'],
                                            
            
            
        ]);
    }
}
