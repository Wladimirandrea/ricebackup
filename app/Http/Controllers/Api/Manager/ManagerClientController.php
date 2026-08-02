<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManagerClientController extends Controller
{
    /**
     * GET /api/manager/clients
     * Clientes asignados al case manager autenticado
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $manager */
        $manager = auth()->user();

        $query = $manager->clients()
            ->withCount([
                'clientAppointments as appointments_count',
                'clientAppointments as pending_count' => fn($q) => $q->where('status', 'pending'),
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('users.name',  'like', "%{$request->search}%")
                        ->orWhere('users.email', 'like', "%{$request->search}%");
                });
            })
            // ✅ addSelect en lugar de select
            ->addSelect([
                'users.id',
                'users.name',
                'users.email',
                'users.profile_image',
                'users.is_active',
                'users.created_at',
            ]);

        $clients = $query->orderBy('users.name')->get()->map(fn($c) => [
            'id'                => $c->id,
            'name'              => $c->name,
            'email'             => $c->email,
            'profile_image_url' => $c->profile_image_url,
            'is_active'         => $c->is_active,
            'created_at'        => $c->created_at->format('Y-m-d'),
            'appointments_count' => $c->appointments_count ?? 0,
            'pending_count'     => $c->pending_count ?? 0,
        ]);

        return response()->json([
            'data'  => $clients,
            'total' => $clients->count(),
        ]);
    }

    /**
     * PATCH /api/manager/clients/{client}/password
     * Cambiar contraseña de un cliente asignado
     */
    public function changePassword(Request $request, User $client): JsonResponse
    {
        /** @var User $manager */
        $manager = auth()->user();

        // Verificar que el cliente esté asignado a este manager
        $isAssigned = DB::table('user_assignments')
            ->where('case_manager_id', $manager->id)
            ->where('client_id', $client->id)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($client->role !== 'client') {
            return response()->json(['message' => 'User is not a client.'], 422);
        }

        $lang = $request->header('Accept-Language', 'en');
        $isEs = str_contains($lang, 'es');

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required'  => $isEs ? 'La contraseña es requerida.' : 'Password is required.',
            'password.min'       => $isEs ? 'Mínimo 8 caracteres.' : 'Minimum 8 characters.',
            'password.confirmed' => $isEs ? 'Las contraseñas no coinciden.' : 'Passwords do not match.',
        ]);

        $client->update(['password' => Hash::make($validated['password'])]);
        $client->tokens()->delete(); // invalidar sesiones activas

        return response()->json([
            'message' => $isEs
                ? 'Contraseña actualizada correctamente.'
                : 'Password updated successfully.',
        ]);
    }

    /**
     * GET /api/manager/clients/{client}
     * Detalle de un cliente asignado
     */
    public function show(User $client): JsonResponse
    {
        /** @var User $manager */
        $manager = auth()->user();

        $isAssigned = DB::table('user_assignments')
            ->where('case_manager_id', $manager->id)
            ->where('client_id', $client->id)
            ->exists();

        if (!$isAssigned) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $appointments = $client->clientAppointments()
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'date'       => $a->date->format('Y-m-d'),
                'start_time' => substr($a->start_time, 0, 5),
                'status'     => $a->status,
            ]);

        return response()->json([
            'data' => [
                'id'                  => $client->id,
                'name'                => $client->name,
                'email'               => $client->email,
                'profile_image_url'   => $client->profile_image_url,
                'is_active'           => $client->is_active,
                'created_at'          => $client->created_at->format('Y-m-d'),
                'appointments_count'  => $client->clientAppointments()->count(),
                'recent_appointments' => $appointments,
            ],
        ]);
    }
}
