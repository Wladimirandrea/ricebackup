<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gifts = [
            [
                'name' => 'Pañales',
                'max_selection' => 10,
                'image_url' => 'https://media.istockphoto.com/id/541001932/es/foto/pa%C3%B1ales-apilados-aislados-sobre-fondo-blanco.jpg?s=1024x1024&w=is&k=20&c=HFgAhJb3yr0WVM5uRNTm9bvVlkbA8eql9hsxa0Big6I=',
                'guest_id' => null,
            ],
            [
                'name' => 'Toallitas humedas',
                'max_selection' => 1,
                'image_url' => 'https://imgs.search.brave.com/P3Mj9Bh6MGp2do0X6uz9hlV4ISGxocUkld4EI09KLpE/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9taXIt/czMtY2RuLWNmLmJl/aGFuY2UubmV0L3By/b2plY3RzLzQwNC9h/MjYyNzcxOTI0OTU3/MTkuWTNKdmNDdzVP/VGtzTnpneUxEQXNN/VEE0LmpwZw==',
                'guest_id' => null,
            ],
            [
                'name' => 'Set de biberones',
                'max_selection' => 3,
                'image_url' => null,
                'guest_id' => null,
            ],
            [
                'name' => 'Ropa de recién nacido',
                'max_selection' => 5,
                'image_url' => null,
                'guest_id' => null,
            ],
        ];

        foreach ($gifts as $gift) {
            DB::table('gifts')->updateOrInsert(
                ['name' => $gift['name']],
                [
                    'max_selection' => $gift['max_selection'],
                    'image_url'     => $gift['image_url'],
                    'guest_id'      => $gift['guest_id'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]
            );
        }
    }
}