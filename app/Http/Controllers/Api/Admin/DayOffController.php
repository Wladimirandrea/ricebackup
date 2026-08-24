<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DayOffRequest;
use App\Models\Appointment;
use App\Models\DayOff;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Mcp\Request;

class DayOffController extends Controller
{
    private function formatDay(DayOff $d): array
    {
        return [
            'id'         => $d->id,
            'date'       => $d->date->format('Y-m-d'),
            'start_time' => substr($d->start_time, 0, 5),
            'end_time'   => substr($d->end_time, 0, 5),
            'reason'     => $d->reason,
        ];
    }

    /** GET /api/admin/days-off */
    public function index(): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $days = DayOff::orderBy('date')
            ->get()
            ->map(fn($d) => $this->formatDay($d));

        return response()->json(['days_off' => $days]);
    }

    /** POST /api/admin/days-off */
    public function store(DayOffRequest $request): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $dayOff = DayOff::create($request->validated());

        return response()->json([
            'message' => 'Day off created successfully.',
            'day_off' => $this->formatDay($dayOff),
        ], 201);
    }

    /** DELETE /api/admin/days-off/{dayOff} */
    public function destroy(DayOff $dayOff): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $dayOff->delete();

        return response()->json(['message' => 'Day off deleted successfully.']);
    }
    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = $request->get('date');
        $manager = $this->client()->caseManagers()->first();

        // 1. Si no tiene manager asignado, no hay horarios
        if (!$manager) {
            return response()->json([
                'is_working' => false,
                'slots'      => []
            ]);
        }

        $carbonDate = \Carbon\Carbon::parse($date);

        // 2. (Opcional) Bloquear fines de semana por defecto
        if ($carbonDate->isWeekend()) {
            return response()->json([
                'is_working' => false,
                'slots'      => []
            ]);
        }

        // 3. Consultar TODOS los DayOffs (Feriados/Ausencias) registrados para esta fecha
        $dayOffs = \App\Models\DayOff::whereDate('date', $date)->get();

        // 4. Buscar las citas que ya tiene ocupadas el manager ese día
        $existingAppointments = Appointment::where('case_manager_id', $manager->id)
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('start_time')
            ->map(fn($time) => substr($time, 0, 5))
            ->toArray();

        $slots = [];

        // 5. Generar horarios base (Ej. 09:00 a 17:00). 
        // *Si tienes otra tabla para horarios de usuarios, puedes reemplazar estas dos líneas*
        $start = \Carbon\Carbon::parse('09:00');
        $end   = \Carbon\Carbon::parse('17:00');

        $availableCount = 0; // Para saber si al final quedó al menos 1 turno disponible

        while ($start < $end) {
            $timeString = $start->format('H:i');

            // A. ¿Ya pasó este horario en el reloj el día de hoy?
            $isPast = false;
            if ($carbonDate->isToday() && $timeString <= now()->format('H:i')) {
                $isPast = true;
            }

            // B. ¿Este horario choca con un DayOff (vacaciones/feriados)?
            $isDayOffTime = false;
            foreach ($dayOffs as $off) {
                $offStart = substr($off->start_time, 0, 5);
                $offEnd   = substr($off->end_time, 0, 5);

                // Si la hora actual (ej. 09:30) está entre el inicio y fin del dayOff, bloqueamos
                if ($timeString >= $offStart && $timeString < $offEnd) {
                    $isDayOffTime = true;
                    break;
                }
            }

            // C. ¿El Case Manager ya tiene una cita a esa hora?
            $hasAppointment = in_array($timeString, $existingAppointments);

            // Estará disponible solo si no ha pasado, no es dayoff y no hay cita
            $isAvailable = !$isPast && !$isDayOffTime && !$hasAppointment;

            if ($isAvailable) {
                $availableCount++;
            }

            $slots[] = [
                'time'      => $timeString,
                'available' => $isAvailable,
            ];

            $start->addMinutes(30); // Saltos de 30 minutos
        }

        // Si todos los slots quedaron ocupados/bloqueados por los DayOffs (día completo libre),
        // devolvemos is_working = false
        $isWorking = $availableCount > 0;

        return response()->json([
            'is_working' => $isWorking,
            'slots'      => $slots
        ]);
    }
}
