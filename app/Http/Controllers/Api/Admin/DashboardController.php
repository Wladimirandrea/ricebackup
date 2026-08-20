<?php
// app/Http/Controllers/Api/Admin/DashboardController.php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard/stats
     *
     * Devuelve todos los datos que necesita el Dashboard en una sola llamada:
     * KPIs, calendario semanal, top 3 case managers y datos de los charts.
     */
    public function stats(Request $request): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $now = now();

        return response()->json([
            'kpis'          => $this->getKpis($now),
            'week'          => $this->getWeek($now),
            'top_managers'  => $this->getTopManagers(),
            'charts'        => $this->getCharts(),
            'server_time'   => $now->toIso8601String(),
        ]);
    }

    /* ───────────────────────── KPIs ───────────────────────── */

    private function getKpis(Carbon $now): array
    {
        return [
            [
                'title'   => 'TOTAL CASOS COMPLETADOS',
                'value'   => Appointment::where('status', 'completed')->count(),
                'footer'  => 'Confirmados',
                'class'   => '',
                'icon'    => 'fa-users',
            ],
            [
                'title'   => 'TOTAL PENDIENTES',
                'value'   => Appointment::where('status', 'pending')->count(),
                'footer'  => 'En Progreso',
                'class'   => 'stat-card--yellow',
                'icon'    => 'fa-clipboard-check',
            ],
            [
                'title'   => 'NUEVOS CLIENTES',
                'value'   => User::where('role', 'client')
                    ->whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)
                    ->count(),
                'footer'  => 'Este mes',
                'class'   => 'stat-card--purple',
                'icon'    => 'fa-calendar-days',
            ],
            [
                'title'   => 'CITAS PARA HOY',
                'value'   => Appointment::whereDate('date', $now->toDateString())
                    ->where('status', '!=', 'cancelled')
                    ->count(),
                'footer'  => 'Programadas',
                'class'   => 'stat-card--red',
                'icon'    => 'fa-bullhorn',
            ],
        ];
    }

    /* ─────────────────── Calendario Semanal ────────────────── */

    private function getWeek(Carbon $now): array
    {
        // Lunes a sábado de la semana actual
        $monday = $now->copy()->startOfWeek(Carbon::MONDAY);
        $saturday = $monday->copy()->addDays(5);

        $appointments = Appointment::with('client:id,name')
            ->whereBetween('date', [$monday->toDateString(), $saturday->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($a) => $a->date->format('Y-m-d'));

        $dayNames = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
        ];

        $chipTypeMap = [
            'pending'   => 'week-chip--pending',
            'confirmed' => 'week-chip--confirmed',
            'completed' => 'week-chip--completed',
        ];

        $days = [];
        for ($i = 0; $i < 6; $i++) {
            $date = $monday->copy()->addDays($i);
            $key  = $date->format('Y-m-d');

            $dayAppointments = $appointments->get($key, collect())->map(function ($a) use ($chipTypeMap) {
                return [
                    'time' => Carbon::parse($a->start_time)->format('h:i A'),
                    'name' => $a->client->name ?? 'N/A',
                    'type' => $chipTypeMap[$a->status] ?? 'week-chip--pending',
                ];
            })->values();

            $days[] = [
                'name'     => $dayNames[$i],
                'date'     => $date->format('d'),
                'isToday'  => $date->isToday(),
                'events'   => $dayAppointments,
            ];
        }

        return $days;
    }

    /* ──────────────────── Top Case Managers ────────────────── */

    private function getTopManagers()
    {
        $managers = User::where('role', 'case_manager')
            ->withCount([
                'appointments as completed_count' => function ($q) {
                    $q->where('status', 'completed');
                },
            ])
            ->orderByDesc('completed_count')
            ->take(3)
            ->get(['id', 'name', 'profile_image']);

        $badgeClasses = ['rank-badge--1', 'rank-badge--2', 'rank-badge--3'];

        return $managers->values()->map(function ($m, $i) use ($badgeClasses) {
            return [
                'rank'          => $i + 1,
                'name'          => $m->name,
                'photo'         => $m->profile_image_url,
                'badgeClass'    => $badgeClasses[$i] ?? 'rank-badge--3',
                'completed'     => $m->completed_count,
            ];
        });
    }

    /* ───────────────────────── Charts ─────────────────────── */

    private function getCharts(): array
    {
        $completed = Appointment::where('status', 'completed')->count();
        $pending   = Appointment::where('status', 'pending')->count();
        $confirmed = Appointment::where('status', 'confirmed')->count();
        $cancelled = Appointment::where('status', 'cancelled')->count();

        $total = $completed + $pending + $confirmed + $cancelled;

        $statusBreakdown = [
            ['label' => 'Cerradas',    'key' => 'completed', 'value' => $completed, 'color' => '#0284c7'],
            ['label' => 'Confirmadas', 'key' => 'confirmed', 'value' => $confirmed, 'color' => '#10b981'],
            ['label' => 'Pendientes',  'key' => 'pending',   'value' => $pending,   'color' => '#f59e0b'],
            ['label' => 'Canceladas',  'key' => 'cancelled', 'value' => $cancelled, 'color' => '#ef4444'],
        ];

        $max = collect($statusBreakdown)->max('value') ?: 1;

        $bars = collect($statusBreakdown)->map(function ($s) use ($max) {
            return [
                'label'      => $s['label'],
                'value'      => $s['value'],
                'color'      => $s['color'],
                'heightPct'  => round(($s['value'] / $max) * 100),
            ];
        });

        return [
            'donut' => [
                'total'    => $total,
                'primary'  => $completed,
                'secondary'=> $pending,
                'legend'   => [
                    ['label' => 'Completed Cases', 'value' => $completed, 'color' => '#10b981'],
                    ['label' => 'Pending Cases',   'value' => $pending,   'color' => '#f59e0b'],
                ],
            ],
            'bars' => [
                'total' => $pending,
                'items' => $bars,
            ],
        ];
    }
}