<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GiftGuestSeeder extends Seeder
{
    public function run(): void
    {
        /*// Asignaciones iniciales de regalos elegidos por invitados
        $assignments = [
            [
                'gift_id'  => 1, // ID del regalo (ej. Pañales)
                'guest_id' => 1, // ID del invitado
            ],
        ];

        foreach ($assignments as $assignment) {
            DB::table('gift_guest')->updateOrInsert(
                [
                    'gift_id'  => $assignment['gift_id'],
                    'guest_id' => $assignment['guest_id'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }*/
    }
}
