<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DayOffRequest;
use App\Models\DayOff;
use App\Models\User;
use Illuminate\Http\JsonResponse;

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
}
