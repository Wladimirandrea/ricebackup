<?php
// database/seeders/AppointmentSeeder.php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener case managers y clientes
        $caseManagers = User::where('role', 'case_manager')->get();
        $clients      = User::where('role', 'client')->get();

        if ($caseManagers->isEmpty() || $clients->isEmpty()) {
            $this->command->info('No case managers or clients found. Skipping.');
            return;
        }

        $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];

        // Crear citas para los próximos 2 meses
        foreach (range(0, 30) as $i) {
            $date        = now()->addDays(rand(-15, 45));
            $startHour   = rand(8, 16);
            $startMinute = rand(0, 1) === 0 ? '00' : '30';
            $startTime   = "{$startHour}:{$startMinute}:00";
            $endTime     = date('H:i:s', strtotime($startTime) + 1800); // +30 min

            Appointment::create([
                'client_id'       => $clients->random()->id,
                'case_manager_id' => $caseManagers->random()->id,
                'date'            => $date->format('Y-m-d'),
                'start_time'      => $startTime,
                'end_time'        => $endTime,
                'status'          => $statuses[array_rand($statuses)],
                'notes'           => rand(0,1) ? 'Nota de prueba #' . $i : null,
            ]);
        }

        $this->command->info('Appointments seeded successfully.');
    }
}