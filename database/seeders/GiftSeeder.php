<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GiftSeeder extends Seeder
{
    public function run(): void
    {
        $gifts = [
            [
                'name' => 'Bañera',
                'max_selection' => 1,
                'image_url' => 'https://i5.walmartimages.com/seo/PC-3-IN-1-BATHTUB_307da927-cae5-4787-a70a-9f4bc6c96d24.e5100ab7195e25a3ee2d09beca367753.jpeg?odnHeight=573&odnWidth=573&odnBg=FFFFFF',
            ],
            [
                'name' => 'Panales',
                'max_selection' => 10,
                'image_url' => 'https://i5.walmartimages.com/seo/Pampers-Swaddlers-Soft-and-Absorbent-Diapers-Size-N-31-Ct_00f72466-29e7-41d1-9062-60f19cae05f5.510f7d5001d9c280601f76b1dafd6b3f.jpeg?odnHeight=573&odnWidth=573&odnBg=FFFFFF',
            ],
            [
                'name' => 'Toallitas húmedas',
                'max_selection' => 10,
                'image_url' => 'https://i5.walmartimages.com/seo/Parent-s-Choice-Shea-Butter-Baby-Wipes-300-Count-Select-for-More-Options_78459058-af4e-48bb-ad61-d14a6d4bc960.38a1af1388c800cd76c27ace68d44776.jpeg?odnHeight=573&odnWidth=573&odnBg=FFFFFF',
            ],
            [
                'name' => 'Ropa de niña 0-3 meses',
                'max_selection' => 5,
                'image_url' => 'https://i.ebayimg.com/images/g/8EUAAeSwjBdqGvqP/s-l500.webp',
            ],
            [
                'name' => 'Ropa de niña 3-6 meses',
                'max_selection' => 5,
                'image_url' => 'https://m.media-amazon.com/images/I/71N8BAICP1L._AC_SX425_.jpg',
            ],
            [
                'name' => 'Kit de cuidado personal bebe',
                'max_selection' => 2,
                'image_url' => 'https://http2.mlstatic.com/D_NQ_NP_816966-MCO91753098399_092025-O.webp',
            ],
            [
                'name' => 'Tetero Anti cólicos',
                'max_selection' => 4,
                'image_url' => 'https://m.media-amazon.com/images/I/71WLmt-O3mL._SL1500_.jpg',
            ],
            [
                'name' => 'Car seat',
                'max_selection' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1544126592-807ade215a0b?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Cuna',
                'max_selection' => 1,
                'image_url' => 'https://m.media-amazon.com/images/I/81JdqjNTQPL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Mecedora',
                'max_selection' => 1,
                'image_url' => 'https://m.media-amazon.com/images/I/71Q4Dj+dubL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Panales de algodón',
                'max_selection' => 5,
                'image_url' => 'https://m.media-amazon.com/images/I/91BKUiCU+wL._AC_UF1000,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Cobijas',
                'max_selection' => 3,
                'image_url' => 'https://m.media-amazon.com/images/I/91AajNOyGqL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Ropa invierno',
                'max_selection' => 4,
                'image_url' => 'https://m.media-amazon.com/images/I/8161norhKtL._AC_UY1000_.jpg',
            ],
            [
                'name' => 'Set de colonia',
                'max_selection' => 2,
                'image_url' => 'https://m.media-amazon.com/images/I/511FeByoHlL._AC_UF1000,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Crema corporal',
                'max_selection' => 3,
                'image_url' => 'https://m.media-amazon.com/images/I/61RAxsnf3aL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Jabones bebe',
                'max_selection' => 5,
                'image_url' => 'https://m.media-amazon.com/images/I/71tQTgv2xtL._AC_UF1000,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Champú bebe',
                'max_selection' => 3,
                'image_url' => 'https://m.media-amazon.com/images/I/619AJ7bvJJL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Toallas de baño',
                'max_selection' => 3,
                'image_url' => 'https://m.media-amazon.com/images/I/61ofzCFvsqL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Kit postparto',
                'max_selection' => 1,
                'image_url' => 'https://m.media-amazon.com/images/I/717eWDJmctL._AC_UF1000,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Pañalera',
                'max_selection' => 1,
                'image_url' => 'https://m.media-amazon.com/images/I/71B0T+3BwwL._AC_UL1500_.jpg',
            ],
            [
                'name' => 'Esponjas absorbente lactancia',
                'max_selection' => 3,
                'image_url' => 'https://m.media-amazon.com/images/I/61xs6uCLzUL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Porta bebe',
                'max_selection' => 1,
                'image_url' => 'https://m.media-amazon.com/images/I/81Yew-KOsNL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Lazos',
                'max_selection' => 5,
                'image_url' => 'https://m.media-amazon.com/images/I/81QgPGk43HL._AC_UF1000,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Almohada lactancia',
                'max_selection' => 1,
                'image_url' => 'https://m.media-amazon.com/images/I/717OdHP5juL._AC_UF894,1000_QL80_FMwebp_.jpg',
            ],
            [
                'name' => 'Muñequitos de apego',
                'max_selection' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?w=800&auto=format&fit=crop&q=80',
            ],
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
