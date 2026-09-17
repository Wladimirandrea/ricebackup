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
                'image_url' => 'https://images.unsplash.com/photo-1584820927498-cfe5211fd8bf?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Panales',
                'max_selection' => 10,
                'image_url' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Toallitas húmedas',
                'max_selection' => 10,
                'image_url' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Ropa de niña 0-3 meses',
                'max_selection' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Ropa de niña 3-6 meses',
                'max_selection' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Kit de cuidado personal bebe',
                'max_selection' => 2,
                'image_url' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Tetero Anti cólicos',
                'max_selection' => 4,
                'image_url' => 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Car seat',
                'max_selection' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1544126592-807ade215a0b?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Cuna',
                'max_selection' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Mecedora',
                'max_selection' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Panales de algodón',
                'max_selection' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1519689680058-324335c77eba?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Cobijas',
                'max_selection' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Ropa invierno',
                'max_selection' => 4,
                'image_url' => 'https://images.unsplash.com/photo-1514090458221-65bb69cf63e6?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Set de colonia',
                'max_selection' => 2,
                'image_url' => 'https://images.unsplash.com/photo-1616949755610-8c9bbc08f138?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Crema corporal',
                'max_selection' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1608248597260-24436573c7b6?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Jabones bebe',
                'max_selection' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1607006344380-b6775a0847a4?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Champú bebe',
                'max_selection' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Toallas de baño',
                'max_selection' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1616627547584-bf28cee262db?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Kit postparto',
                'max_selection' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1584017911766-d451b3d0e843?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Pañalera',
                'max_selection' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Esponjas absorbente lactancia',
                'max_selection' => 3,
                'image_url' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Porta bebe',
                'max_selection' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Lazos',
                'max_selection' => 5,
                'image_url' => 'https://images.unsplash.com/photo-1607083206869-4c7672e72a8a?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'name' => 'Almohada lactancia',
                'max_selection' => 1,
                'image_url' => 'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?w=800&auto=format&fit=crop&q=80',
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
