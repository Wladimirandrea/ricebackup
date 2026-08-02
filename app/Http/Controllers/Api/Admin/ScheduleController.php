<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateScheduleDayRequest;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /api/admin/schedule
     * Retorna los 7 días ordenados Lunes-Domingo
     */
    public function index(): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $schedules = Schedule::orderByRaw("FIELD(day_of_week, 1,2,3,4,5,6,0)")
            ->get()
            ->map(fn($s) => [
                'id'          => $s->id,
                'day_of_week' => $s->day_of_week,
                'is_working'  => $s->is_working,
                'start_time'  => $s->start_time ? substr($s->start_time, 0, 5) : null,
                'end_time'    => $s->end_time   ? substr($s->end_time, 0, 5)   : null,
            ]);

        return response()->json(['data' => $schedules]);
    }

    /**
     * PUT /api/admin/schedule/{day}
     * Actualiza el horario de un día
     */
    public function update(UpdateScheduleDayRequest $request, Schedule $schedule): JsonResponse
    {
        $schedule->update([
            'is_working' => $request->is_working,
            'start_time' => $request->is_working ? $request->start_time : null,
            'end_time'   => $request->is_working ? $request->end_time   : null,
        ]);

        return response()->json([
            'message' => 'Schedule updated successfully',
            'data'    => [
                'id'          => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'is_working'  => $schedule->is_working,
                'start_time'  => $schedule->start_time ? substr($schedule->start_time, 0, 5) : null,
                'end_time'    => $schedule->end_time   ? substr($schedule->end_time, 0, 5)   : null,
            ],
        ]);
    }
}
