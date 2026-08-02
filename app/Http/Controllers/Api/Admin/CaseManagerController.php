<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CaseManager\ListCaseManagersRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CaseManagerController extends Controller
{
    use AuthorizesRequests;



    public function index(ListCaseManagersRequest $request): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $query = User::where('role', 'case_manager')
            ->withCount('clients')  // ← ahora usa BelongsToMany
            ->when(
                $request->search,
                fn($q) =>
                $q->where(
                    fn($q2) =>
                    $q2->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                )
            )
            ->orderBy('name');

        $managers = $query->paginate($request->per_page ?? 12);

        return response()->json([
            'data' => $managers->map(fn($m) => [
                'id'            => $m->id,
                'name'          => $m->name,
                'email'         => $m->email,
                'role'          => $m->role,
                'profile_image' => $m->profile_image_url,
                'clients_count' => $m->clients_count,
            ]),
            'meta' => [
                'current_page' => $managers->currentPage(),
                'last_page'    => $managers->lastPage(),
                'per_page'     => $managers->perPage(),
                'total'        => $managers->total(),
            ],
        ]);
    }

    public function clients(User $user): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);
        abort_if($user->role !== 'case_manager', 404);

        $clients = $user->clients()
            ->select('users.id', 'users.name', 'users.email', 'users.profile_image')
            ->orderBy('users.name')
            ->get()
            ->map(fn($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'email'         => $c->email,
                'profile_image' => $c->profile_image_url,
            ]);

        return response()->json([
            'case_manager' => ['id' => $user->id, 'name' => $user->name],
            'clients'      => $clients,
            'total'        => $clients->count(),
        ]);
    }

    public function unassignedClients(): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        // Clientes que NO tienen ninguna asignación en user_assignments
        $clients = User::where('role', 'client')
            ->whereDoesntHave('caseManagers')  // ← usa la nueva relación
            ->select('id', 'name', 'email', 'profile_image')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'            => $c->id,
                'name'          => $c->name,
                'email'         => $c->email,
                'profile_image' => $c->profile_image_url,
            ]);

        return response()->json([
            'clients' => $clients,
            'total'   => $clients->count(),
        ]);
    }

    public function reassign(Request $request): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $request->validate([
            'client_id'      => ['required', 'exists:users,id'],
            'case_manager_id' => ['required', 'exists:users,id'],
        ]);

        $client      = User::findOrFail($request->client_id);
        $caseManager = User::findOrFail($request->case_manager_id);

        abort_if($client->role !== 'client', 422);
        abort_if($caseManager->role !== 'case_manager', 422);

        // Eliminar asignaciones anteriores e insertar nueva
        DB::table('user_assignments')
            ->where('client_id', $client->id)
            ->delete();

        DB::table('user_assignments')->insert([
            'case_manager_id' => $caseManager->id,
            'client_id'       => $client->id,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json(['message' => 'Client reassigned successfully']);
    }

    /**
     * DELETE /api/admin/case-managers/release/{client}
     * Liberar cliente (sin case manager)
     */
    public function release(User $client): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);
        abort_if($client->role !== 'client', 422);

        DB::table('user_assignments')
            ->where('client_id', $client->id)
            ->delete();

        return response()->json(['message' => 'Client released successfully']);
    }

    /**
     * GET /api/admin/case-managers/all
     * Lista simple de todos los case managers (para dropdown reasignación)
     */
    public function allManagers(): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $managers = User::where('role', 'case_manager')
            ->select('id', 'name', 'email', 'profile_image')
            ->orderBy('name')
            ->get()
            ->map(fn($m) => [
                'id'            => $m->id,
                'name'          => $m->name,
                'email'         => $m->email,
                'profile_image' => $m->profile_image_url,
            ]);

        return response()->json(['managers' => $managers]);
    }

    public function clientManager(User $client): JsonResponse
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $assignment = DB::table('user_assignments')
            ->where('client_id', $client->id)
            ->first();

        return response()->json([
            'case_manager_id' => $assignment?->case_manager_id ?? null,
        ]);
    }
}
