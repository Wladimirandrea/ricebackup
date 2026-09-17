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
                'image_url' => 'https://i5.walmartimages.com/seo/Huggies-Aloe-Vitamin-E-Baby-Wipes-for-Sensitive-Skin-Unscented-1-Flip-Top-Pack-176-Wipes_892a867f-9d6b-425c-94d6-7421640c85c5.776ae215da716f05056fc0d3fec76b6e.jpeg?odnHeight=573&odnWidth=573&odnBg=FFFFFF',
                'guest_id' => null,
            ],
            [
                'name' => 'Set de biberones',
                'max_selection' => 1,
                'image_url' => 'https://http2.mlstatic.com/D_NQ_NP_902095-MLA99463965546_112025-O.webp',
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