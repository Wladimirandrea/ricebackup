<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GiftSeeder extends Seeder
{
    public function run(): void
    {
        $gifts = [
            ['name' => 'Bañera', 'max_selection' => 1, 'image_url' => null],
            ['name' => 'Panales', 'max_selection' => 10, 'image_url' => 'https://media.istockphoto.com/id/541001932/es/foto/pa%C3%B1ales-apilados-aislados-sobre-fondo-blanco.jpg?s=1024x1024&w=is&k=20&c=HFgAhJb3yr0WVM5uRNTm9bvVlkbA8eql9hsxa0Big6I='],
            ['name' => 'Toallitas húmedas', 'max_selection' => 10, 'image_url' => null],
            ['name' => 'Ropa de niña 0-3 meses', 'max_selection' => 5, 'image_url' => null],
            ['name' => 'Ropa de niña 3-6 meses', 'max_selection' => 5, 'image_url' => null],
            ['name' => 'Kit de cuidado personal bebe', 'max_selection' => 2, 'image_url' => null],
            ['name' => 'Tetero Anti cólicos', 'max_selection' => 4, 'image_url' => null],
            ['name' => 'Car seat', 'max_selection' => 1, 'image_url' => null],
            ['name' => 'Cuna', 'max_selection' => 1, 'image_url' => null],
            ['name' => 'Mecedora', 'max_selection' => 1, 'image_url' => null],
            ['name' => 'Panales de algodón', 'max_selection' => 5, 'image_url' => null],
            ['name' => 'Cobijas', 'max_selection' => 3, 'image_url' => null],
            ['name' => 'Ropa invierno', 'max_selection' => 4, 'image_url' => null],
            ['name' => 'Set de colonia', 'max_selection' => 2, 'image_url' => null],
            ['name' => 'Crema corporal', 'max_selection' => 3, 'image_url' => null],
            ['name' => 'Jabones bebe', 'max_selection' => 5, 'image_url' => null],
            ['name' => 'Champú bebe', 'max_selection' => 3, 'image_url' => null],
            ['name' => 'Toallas de baño', 'max_selection' => 3, 'image_url' => null],
            ['name' => 'Kit postparto', 'max_selection' => 1, 'image_url' => null],
            ['name' => 'Pañalera', 'max_selection' => 1, 'image_url' => null],
            ['name' => 'Esponjas absorbente lactancia', 'max_selection' => 3, 'image_url' => null],
            ['name' => 'Porta bebe', 'max_selection' => 1, 'image_url' => null],
            ['name' => 'Lazos', 'max_selection' => 5, 'image_url' => null],
            ['name' => 'Almohada lactancia', 'max_selection' => 1, 'image_url' => null],
            ['name' => 'Muñequitos de apego', 'max_selection' => 3, 'image_url' => null],
        ];

        foreach ($gifts as $gift) {
            DB::table('gifts')->updateOrInsert(
                ['name' => $gift['name']],
                [
                    'max_selection' => $gift['max_selection'],
                    'image_url'     => $gift['image_url'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }
    }
}
