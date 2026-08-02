<?php
// app/Http/Controllers/Api/Admin/AppointmentController.php

namespace App\Http\Controllers\Api\Admin;

use App\Events\AppointmentCreatedEvent;
use App\Events\AppointmentStatusUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Mail\AppointmentCreatedMail;
use App\Models\Appointment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /api/admin/appointments/calendar?month=5&year=2026
     */
    public function calendar(Request $request): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $month = $request->get('month', now()->month);
        $year  = $request->get('year',  now()->year);

        $appointments = Appointment::with(['client:id,name', 'caseManager:id,name'])
            ->whereMonth('date', $month)
            ->whereYear('date',  $year)
            ->get();

        $grouped = $appointments->groupBy(fn($a) => $a->date->format('Y-m-d'));

        $calendar = $grouped->map(function ($dayAppts) {
            $counts = [
                'pending'   => 0,
                'confirmed' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'total'     => $dayAppts->count(),
            ];
            foreach ($dayAppts as $a) $counts[$a->status]++;
            return $counts;
        });

        $daysOff = \App\Models\DayOff::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->keyBy(fn($d) => $d->date->format('Y-m-d'))
            ->map(fn($d) => [
                'reason'     => $d->reason,
                'start_time' => substr($d->start_time, 0, 5),
                'end_time'   => substr($d->end_time, 0, 5),
            ]);

        return response()->json([
            'month'    => $month,
            'year'     => $year,
            'calendar' => $calendar,
            'days_off' => $daysOff,
        ]);
    }

    /**
     * GET /api/admin/appointments/day?date=2026-05-21
     */
    public function day(Request $request): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $date      = $request->get('date', now()->format('Y-m-d'));
        $dayOfWeek = (int) date('w', strtotime($date));
        $schedule  = \App\Models\Schedule::where('day_of_week', $dayOfWeek)->first();
        $dayOff    = \App\Models\DayOff::whereDate('date', $date)->first();

        $appointments = Appointment::with([
            'client:id,name,profile_image',
            'caseManager:id,name,profile_image',
        ])
            ->whereDate('date', $date)
            ->orderBy('start_time')
            ->get()
            ->map(fn($a) => [
                'id'           => $a->id,
                'date'         => $a->date->format('Y-m-d'),
                'start_time'   => substr($a->start_time, 0, 5),
                'end_time'     => substr($a->end_time, 0, 5),
                'status'       => $a->status,
                'notes'        => $a->notes,
                'client'       => [
                    'id'            => $a->client->id,
                    'name'          => $a->client->name,
                    'profile_image' => $a->client->profile_image_url,
                ],
                'case_manager' => [
                    'id'            => $a->caseManager->id,
                    'name'          => $a->caseManager->name,
                    'profile_image' => $a->caseManager->profile_image_url,
                ],
            ]);

        $caseManagers = $appointments->pluck('case_manager')->unique('id')->values();

        $availableSlots = [];
        $occupiedSlots  = $appointments
            ->where('status', '!=', 'cancelled')
            ->pluck('start_time')
            ->toArray();

        if (!$dayOff && $schedule?->is_working && $schedule->start_time && $schedule->end_time) {
            $start = substr($schedule->start_time, 0, 5);
            $end   = substr($schedule->end_time,   0, 5);

            [$sh, $sm] = array_map('intval', explode(':', $start));
            [$eh, $em] = array_map('intval', explode(':', $end));

            $h = $sh;
            $m = $sm;
            while ($h < $eh || ($h === $eh && $m < $em)) {
                $slot = sprintf('%02d:%02d', $h, $m);
                $availableSlots[] = [
                    'time'      => $slot,
                    'available' => !in_array($slot, $occupiedSlots),
                ];
                $m += 30;
                if ($m >= 60) {
                    $m = 0;
                    $h++;
                }
            }
        }

        return response()->json([
            'date'            => $date,
            'appointments'    => $appointments,
            'case_managers'   => $caseManagers,
            'available_slots' => $availableSlots,
            'is_day_off'      => (bool) $dayOff,
            'day_off_info'    => $dayOff ? [
                'reason'     => $dayOff->reason,
                'start_time' => substr($dayOff->start_time, 0, 5),
                'end_time'   => substr($dayOff->end_time,   0, 5),
            ] : null,
            'schedule' => [
                'is_working' => $dayOff ? false : (bool) ($schedule?->is_working ?? false),
                'start_time' => $schedule?->start_time ? substr($schedule->start_time, 0, 5) : null,
                'end_time'   => $schedule?->end_time   ? substr($schedule->end_time, 0, 5)   : null,
            ],
        ]);
    }

    /**
     * POST /api/admin/appointments
     */
    public function store(Request $request): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $lang = $request->header('Accept-Language', 'en');
        $isEs = str_contains($lang, 'es');

        $validated = $request->validate([
            'client_id'       => ['required', 'exists:users,id'],
            'case_manager_id' => ['required', 'exists:users,id'],
            'date'            => ['required', 'date'],
            'start_time'      => ['required', 'date_format:H:i'],
            'status'          => ['required', 'in:pending,confirmed,completed,cancelled'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        $validated['end_time'] = date('H:i', strtotime($validated['start_time']) + 1800);

        // Verificar día no laborable
        $dayOfWeek = (int) date('w', strtotime($validated['date']));
        $schedule  = \App\Models\Schedule::where('day_of_week', $dayOfWeek)->first();

        if (!$schedule?->is_working) {
            return response()->json([
                'message' => $isEs
                    ? 'No se pueden agendar citas en días no laborables.'
                    : 'Appointments cannot be scheduled on non-working days.',
            ], 422);
        }

        // Verificar day off
        $dayOff = \App\Models\DayOff::whereDate('date', $validated['date'])->first();
        if ($dayOff) {
            $startTime   = $validated['start_time'];
            $dayOffStart = substr($dayOff->start_time, 0, 5);
            $dayOffEnd   = substr($dayOff->end_time,   0, 5);

            if ($startTime >= $dayOffStart && $startTime < $dayOffEnd) {
                return response()->json([
                    'message' => $isEs
                        ? 'Este horario no está disponible debido a un día libre.'
                        : 'This time slot is not available due to a day off.',
                ], 422);
            }
        }

        // Verificar conflicto por case manager
        $cmConflict = Appointment::where('case_manager_id', $validated['case_manager_id'])
            ->whereDate('date', $validated['date'])
            ->where('start_time', $validated['start_time'] . ':00')
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($cmConflict) {
            return response()->json([
                'message' => $isEs
                    ? 'Este gestor ya tiene una cita a esta hora.'
                    : 'This case manager already has an appointment at this time.',
            ], 422);
        }

        // Verificar conflicto por cliente
        $clientConflict = Appointment::where('client_id', $validated['client_id'])
            ->whereDate('date', $validated['date'])
            ->where('start_time', $validated['start_time'] . ':00')
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($clientConflict) {
            return response()->json([
                'message' => $isEs
                    ? 'Este cliente ya tiene una cita a esta hora.'
                    : 'This client already has an appointment at this time.',
            ], 422);
        }

        // Crear cita
        $appointment = Appointment::create($validated);
        $appointment->load('client:id,name,email,profile_image', 'caseManager:id,name,email,profile_image');

        //enviar evento
        broadcast(new AppointmentCreatedEvent($appointment))->toOthers();

        // Enviar emails
        $locale = $isEs ? 'es' : 'en';
        $devEmail = app()->environment('local') ? 'wladimirandrea2@gmail.com' : null;

        try {
            // Correo al cliente
            Mail::to($devEmail ?? $appointment->client->email)
                ->send(new AppointmentCreatedMail($appointment, 'client', $locale));

            // Correo al case manager
            Mail::to($devEmail ?? $appointment->caseManager->email)
                ->send(new AppointmentCreatedMail($appointment, 'case_manager', $locale));
        } catch (\Exception $e) {
            Log::error('Error sending appointment email: ' . $e->getMessage());
        }

        return response()->json([
            'message'     => 'Appointment created successfully.',
            'appointment' => [
                'id'           => $appointment->id,
                'date'         => $appointment->date->format('Y-m-d'),
                'start_time'   => substr($appointment->start_time, 0, 5),
                'end_time'     => substr($appointment->end_time, 0, 5),
                'status'       => $appointment->status,
                'notes'        => $appointment->notes,
                'client'       => [
                    'id'            => $appointment->client->id,
                    'name'          => $appointment->client->name,
                    'profile_image' => $appointment->client->profile_image_url,
                ],
                'case_manager' => [
                    'id'            => $appointment->caseManager->id,
                    'name'          => $appointment->caseManager->name,
                    'profile_image' => $appointment->caseManager->profile_image_url,
                ],
            ],
        ], 201);
    }

    /**
     * PATCH /api/admin/appointments/{appointment}/status
     */
    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $previousStatus = $appointment->status;

        $appointment->update($validated);

        if ($previousStatus !== $appointment->status) {
            broadcast(new AppointmentStatusUpdatedEvent($appointment, $previousStatus, 'admin'));
        }

        return response()->json(['message' => 'Status updated successfully.']);
    }



    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $lang = $request->header('Accept-Language', 'en');
        $isEs = str_contains($lang, 'es');

        $validated = $request->validate([
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $validated['end_time'] = date('H:i', strtotime($validated['start_time']) + 1800);

        // Verificar día no laborable
        $dayOfWeek = (int) date('w', strtotime($validated['date']));
        $schedule  = \App\Models\Schedule::where('day_of_week', $dayOfWeek)->first();

        if (!$schedule?->is_working) {
            return response()->json([
                'message' => $isEs
                    ? 'No se pueden agendar citas en días no laborables.'
                    : 'Appointments cannot be scheduled on non-working days.',
            ], 422);
        }

        // Verificar day off
        $dayOff = \App\Models\DayOff::whereDate('date', $validated['date'])->first();
        if ($dayOff) {
            $dayOffStart = substr($dayOff->start_time, 0, 5);
            $dayOffEnd   = substr($dayOff->end_time,   0, 5);
            if ($validated['start_time'] >= $dayOffStart && $validated['start_time'] < $dayOffEnd) {
                return response()->json([
                    'message' => $isEs
                        ? 'Este horario no está disponible debido a un día libre.'
                        : 'This time slot is not available due to a day off.',
                ], 422);
            }
        }

        // Verificar conflicto por case manager (excluyendo la cita actual)
        $cmConflict = Appointment::where('case_manager_id', $appointment->case_manager_id)
            ->whereDate('date', $validated['date'])
            ->where('start_time', $validated['start_time'] . ':00')
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($cmConflict) {
            return response()->json([
                'message' => $isEs
                    ? 'Este gestor ya tiene una cita a esta hora.'
                    : 'This case manager already has an appointment at this time.',
            ], 422);
        }

        // Verificar conflicto por cliente (excluyendo la cita actual)
        $clientConflict = Appointment::where('client_id', $appointment->client_id)
            ->whereDate('date', $validated['date'])
            ->where('start_time', $validated['start_time'] . ':00')
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($clientConflict) {
            return response()->json([
                'message' => $isEs
                    ? 'Este cliente ya tiene una cita a esta hora.'
                    : 'This client already has an appointment at this time.',
            ], 422);
        }

        $appointment->update($validated);

        return response()->json([
            'message' => 'Appointment updated successfully.',
            'appointment' => [
                'id'         => $appointment->id,
                'date'       => $appointment->date->format('Y-m-d'),
                'start_time' => substr($appointment->start_time, 0, 5),
                'end_time'   => substr($appointment->end_time,   0, 5),
                'status'     => $appointment->status,
                'notes'      => $appointment->notes,
            ],
        ]);
    }

    /**
     * GET /api/admin/appointments/slots?date=2026-05-26&case_manager_id=2
     */
    public function slots(Request $request): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $request->validate([
            'date'            => ['required', 'date'],
            'case_manager_id' => ['required', 'exists:users,id'],
        ]);

        $date      = $request->date;
        $managerId = $request->case_manager_id;
        $dayOfWeek = (int) date('w', strtotime($date));
        $schedule  = \App\Models\Schedule::where('day_of_week', $dayOfWeek)->first();

        if (!$schedule?->is_working) {
            return response()->json(['slots' => [], 'is_working' => false]);
        }

        $dayOff = \App\Models\DayOff::whereDate('date', $date)->first();

        $cmOccupied = Appointment::where('case_manager_id', $managerId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->pluck('start_time')
            ->map(fn($t) => substr($t, 0, 5))
            ->toArray();

        $slots = [];
        $start = substr($schedule->start_time, 0, 5);
        $end   = substr($schedule->end_time,   0, 5);

        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));

        $h = $sh;
        $m = $sm;
        while ($h < $eh || ($h === $eh && $m < $em)) {
            $slot = sprintf('%02d:%02d', $h, $m);

            $isDayOff = false;
            if ($dayOff) {
                $dayOffStart = substr($dayOff->start_time, 0, 5);
                $dayOffEnd   = substr($dayOff->end_time,   0, 5);
                $isDayOff    = $slot >= $dayOffStart && $slot < $dayOffEnd;
            }

            $slots[] = [
                'time'      => $slot,
                'available' => !in_array($slot, $cmOccupied) && !$isDayOff,
                'cm_taken'  => in_array($slot, $cmOccupied),
                'day_off'   => $isDayOff,
            ];

            $m += 30;
            if ($m >= 60) {
                $m = 0;
                $h++;
            }
        }

        return response()->json([
            'slots'      => $slots,
            'is_working' => true,
        ]);
    }
}
