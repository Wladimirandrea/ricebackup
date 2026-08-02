<?php
// app/Http/Controllers/Api/Manager/ManagerAppointmentController.php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Mail\AppointmentCreatedMail;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Events\AppointmentCreatedEvent;
use App\Events\AppointmentStatusUpdatedEvent;

class ManagerAppointmentController extends Controller
{
    private function manager()
    {
        return auth()->user();
    }


    public function clients(): JsonResponse
    {
        $clients = $this->manager()->clients()
            ->select('users.id', 'users.name', 'users.email', 'users.profile_image')
            ->orderBy('users.name')
            ->get()
            ->map(fn($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'email'         => $c->email,
                'profile_image_url' => $c->profile_image_url,
            ]);

        return response()->json(['clients' => $clients]);
    }


    public function calendar(Request $request): JsonResponse
    {
        try {
            $month    = $request->get('month', now()->month);
            $year     = $request->get('year',  now()->year);
            $clientId = $request->get('client_id');

            $query = Appointment::with(['client', 'caseManager'])
                ->where('case_manager_id', $this->manager()->id)
                ->whereMonth('date', $month)
                ->whereYear('date',  $year);

            if ($clientId) $query->where('client_id', $clientId);

            $appointments = $query->get();

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
                'calendar' => $calendar,
                'days_off' => $daysOff,
                'month'    => (int) $month,
                'year'     => (int) $year,
            ]);
        } catch (\Exception $e) {
            \Log::error('Calendar error:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function day(Request $request): JsonResponse
    {
        $date      = $request->get('date', now()->format('Y-m-d'));
        $clientId  = $request->get('client_id');
        $dayOfWeek = (int) date('w', strtotime($date));
        $schedule  = \App\Models\Schedule::where('day_of_week', $dayOfWeek)->first();
        $dayOff    = \App\Models\DayOff::whereDate('date', $date)->first();

        $query = Appointment::with(['client', 'caseManager'])
            ->where('case_manager_id', $this->manager()->id)
            ->whereDate('date', $date)
            ->orderBy('start_time');

        if ($clientId) $query->where('client_id', $clientId);

        $appointments = $query->get()->map(fn($a) => [
            'id'           => $a->id,
            'date'         => $a->date->format('Y-m-d'),
            'start_time'   => substr($a->start_time, 0, 5),
            'end_time'     => substr($a->end_time, 0, 5),
            'status'       => $a->status,
            'notes'        => $a->notes,
            'client'       => $a->client ? [
                'id'            => $a->client->id,
                'name'          => $a->client->name,
                'profile_image' => $a->client->profile_image_url,
            ] : null,
            'case_manager' => $a->caseManager ? [
                'id'            => $a->caseManager->id,
                'name'          => $a->caseManager->name,
                'profile_image' => $a->caseManager->profile_image_url,
            ] : null,
        ]);

        $availableSlots = [];
        $occupiedSlots  = $appointments->where('status', '!=', 'cancelled')->pluck('start_time')->toArray();

        if (!$dayOff && $schedule?->is_working && $schedule->start_time && $schedule->end_time) {
            $start = substr($schedule->start_time, 0, 5);
            $end   = substr($schedule->end_time,   0, 5);
            [$sh, $sm] = array_map('intval', explode(':', $start));
            [$eh, $em] = array_map('intval', explode(':', $end));
            $h = $sh;
            $m = $sm;
            while ($h < $eh || ($h === $eh && $m < $em)) {
                $slot           = sprintf('%02d:%02d', $h, $m);
                $availableSlots[] = ['time' => $slot, 'available' => !in_array($slot, $occupiedSlots)];
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

    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date      = $request->date;
        $managerId = $this->manager()->id;
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
            $slot     = sprintf('%02d:%02d', $h, $m);
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

        return response()->json(['slots' => $slots, 'is_working' => true]);
    }


    public function store(Request $request): JsonResponse
    {
        $lang = $request->header('Accept-Language', 'en');
        $isEs = str_contains($lang, 'es');

        $validated = $request->validate([
            'client_id'  => ['required', 'exists:users,id'],
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'status'     => ['required', 'in:pending,confirmed,completed,cancelled'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $validated['case_manager_id'] = $this->manager()->id;
        $validated['end_time']        = date('H:i', strtotime($validated['start_time']) + 1800);

        $isMyClient = $this->manager()->clients()->where('users.id', $validated['client_id'])->exists();
        abort_if(!$isMyClient, 403, $isEs ? 'Este cliente no te pertenece.' : 'This client does not belong to you.');

        $dayOfWeek = (int) date('w', strtotime($validated['date']));
        $schedule  = \App\Models\Schedule::where('day_of_week', $dayOfWeek)->first();
        if (!$schedule?->is_working) {
            return response()->json(['message' => $isEs ? 'No se pueden agendar citas en días no laborables.' : 'Appointments cannot be scheduled on non-working days.'], 422);
        }

        $dayOff = \App\Models\DayOff::whereDate('date', $validated['date'])->first();
        if ($dayOff) {
            $dayOffStart = substr($dayOff->start_time, 0, 5);
            $dayOffEnd   = substr($dayOff->end_time,   0, 5);
            if ($validated['start_time'] >= $dayOffStart && $validated['start_time'] < $dayOffEnd) {
                return response()->json(['message' => $isEs ? 'Este horario no está disponible debido a un día libre.' : 'This time slot is not available due to a day off.'], 422);
            }
        }

        $conflict = Appointment::where('case_manager_id', $validated['case_manager_id'])
            ->whereDate('date', $validated['date'])
            ->where('start_time', $validated['start_time'] . ':00')
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($conflict) {
            return response()->json(['message' => $isEs ? 'Ya tienes una cita a esta hora.' : 'You already have an appointment at this time.'], 422);
        }

        $appointment = Appointment::create($validated);
        $appointment->load('client:id,name,profile_image', 'caseManager:id,name,profile_image');

        event(new AppointmentCreatedEvent($appointment));

        try {
            $locale = $isEs ? 'es' : 'en';
            Mail::to($appointment->client->email)->send(new AppointmentCreatedMail($appointment, 'client', $locale));
            Mail::to($appointment->caseManager->email)->send(new AppointmentCreatedMail($appointment, 'case_manager', $locale));
        } catch (\Exception $e) {
            Log::error('Email error: ' . $e->getMessage());
        }

        return response()->json([
            'message'     => 'Appointment created successfully.',
            'appointment' => [
                'id'           => $appointment->id,
                'date'         => $appointment->date->format('Y-m-d'),
                'start_time'   => substr($appointment->start_time, 0, 5),
                'end_time'     => substr($appointment->end_time,   0, 5),
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


    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        abort_if($appointment->case_manager_id !== $this->manager()->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,completed,cancelled'],
        ]);

        $previousStatus = $appointment->status;

        $appointment->update($validated);

        if ($previousStatus !== $appointment->status) {
            broadcast(new AppointmentStatusUpdatedEvent($appointment, $previousStatus, 'case_manager'))
                ->toOthers();
        }

        return response()->json(['message' => 'Status updated successfully.']);
    }
    /** PUT /api/manager/appointments/{appointment} */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        abort_if($appointment->case_manager_id !== $this->manager()->id, 403);

        $lang = $request->header('Accept-Language', 'en');
        $isEs = str_contains($lang, 'es');

        $validated = $request->validate([
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $validated['end_time'] = date('H:i', strtotime($validated['start_time']) + 1800);

        $dayOfWeek = (int) date('w', strtotime($validated['date']));
        $schedule  = \App\Models\Schedule::where('day_of_week', $dayOfWeek)->first();
        if (!$schedule?->is_working) {
            return response()->json(['message' => $isEs ? 'No se pueden agendar citas en días no laborables.' : 'Appointments cannot be scheduled on non-working days.'], 422);
        }

        $conflict = Appointment::where('case_manager_id', $this->manager()->id)
            ->whereDate('date', $validated['date'])
            ->where('start_time', $validated['start_time'] . ':00')
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($conflict) {
            return response()->json(['message' => $isEs ? 'Ya tienes una cita a esta hora.' : 'You already have an appointment at this time.'], 422);
        }

        $appointment->update($validated);
        return response()->json([
            'message'     => 'Updated successfully.',
            'appointment' => [
                'id'         => $appointment->id,
                'date'       => $appointment->date->format('Y-m-d'),
                'start_time' => substr($appointment->start_time, 0, 5),
                'end_time'   => substr($appointment->end_time, 0, 5),
                'status'     => $appointment->status,
                'notes'      => $appointment->notes,
            ],
        ]);
    }
}
