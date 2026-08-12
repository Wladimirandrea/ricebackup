<?php
// app/Http/Controllers/Api/Client/ClientAppointmentController.php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Events\AppointmentStatusUpdatedEvent;

class ClientAppointmentController extends Controller
{
    private function client()
    {
        return auth()->user();
    }

    /** GET /api/client/case-manager */
    public function caseManager(): JsonResponse
    {
        $manager = $this->client()->caseManagers()->first();

        return response()->json([
            'case_manager' => $manager ? [
                'id'                => $manager->id,
                'name'              => $manager->name,
                'email'             => $manager->email,
                'profile_image_url' => $manager->profile_image_url,
            ] : null,
        ]);
    }

    /** GET /api/client/appointments/calendar?month=&year= */
    public function calendar(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->month);
        $year  = $request->get('year',  now()->year);

        $appointments = Appointment::where('client_id', $this->client()->id)
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

        return response()->json([
            'calendar' => $calendar,
            'month'    => (int) $month,
            'year'     => (int) $year,
        ]);
    }

    /** GET /api/client/appointments/day?date= */
    public function day(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        $appointments = Appointment::with('caseManager:id,name,profile_image')
            ->where('client_id', $this->client()->id)
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
                'case_manager' => $a->caseManager ? [
                    'id'                => $a->caseManager->id,
                    'name'              => $a->caseManager->name,
                    'profile_image_url' => $a->caseManager->profile_image_url,
                ] : null,
            ]);

        return response()->json([
            'date'         => $date,
            'appointments' => $appointments,
        ]);
    }

    /** GET /api/client/appointments/list?status=pending */
    public function index(Request $request): JsonResponse
    {
        $status   = $request->get('status', 'all');
        $clientId = $this->client()->id;

        $base = Appointment::where('client_id', $clientId);
        $counts = [
            'all'       => (clone $base)->count(),
            'pending'   => (clone $base)->where('status', 'pending')->count(),
            'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
        ];

        $query = Appointment::with('caseManager:id,name,profile_image')
            ->where('client_id', $clientId)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $appointments = $query->get()->map(fn($a) => [
            'id'           => $a->id,
            'date'         => $a->date->format('Y-m-d'),
            'start_time'   => substr($a->start_time, 0, 5),
            'end_time'     => substr($a->end_time, 0, 5),
            'status'       => $a->status,
            'notes'        => $a->notes,
            'case_manager' => $a->caseManager ? [
                'id'                => $a->caseManager->id,
                'name'              => $a->caseManager->name,
                'profile_image_url' => $a->caseManager->profile_image_url,
            ] : null,
        ]);

        return response()->json([
            'appointments' => $appointments,
            'counts'       => $counts,
        ]);
    }

    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $lang = $request->header('Accept-Language', 'en');
        $isEs = str_contains($lang, 'es');

        abort_if($appointment->client_id !== $this->client()->id, 403);

        $request->validate([
            'status' => ['required', 'in:cancelled'],
        ]);

        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => $isEs
                    ? 'Esta cita ya no se puede cancelar.'
                    : 'This appointment can no longer be cancelled.',
            ], 422);
        }

        $previousStatus = $appointment->status;

        $appointment->update(['status' => 'cancelled']);

        broadcast(new AppointmentStatusUpdatedEvent($appointment, $previousStatus, 'client'));

        return response()->json(['message' => 'Appointment cancelled successfully.']);
    }
}
