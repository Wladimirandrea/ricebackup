<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::whereIn('role', ['admin', 'case_manager', 'client'])
            ->where('id', '!=', auth()->id()) // ← excluir al admin autenticado
            ->with('creator');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name',  'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $users->map(fn($user) => $this->formatUser($user)),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = [
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $request->role,
            'created_by' => auth()->id(),
            'is_active'  => true,
        ];

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')
                ->store('profile-images', 'public');
        }

        $user = User::create($data);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data'    => $this->formatUser($user),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $this->formatUser($user->load('creator')),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,case_manager,client',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean',
        ]);

        // Si sube una nueva imagen
        if ($request->hasFile('profile_image')) {

            // Eliminar la imagen anterior si existe
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $request->file('profile_image')->store('profile-images', 'public');
            $validated['profile_image'] = $path;
        } else {
            // Si no sube imagen nueva, no tocar el campo
            unset($validated['profile_image']);
        }

        // Solo actualizar password si se envió uno
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    public function caseManagers(): JsonResponse
    {
        $managers = User::where('role', 'case_manager')
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'profile_image')
            ->get()
            ->map(fn($u) => [
                'id'                => $u->id,
                'name'              => $u->name,
                'email'             => $u->email,
                'profile_image_url' => $u->profile_image_url,
            ]);

        return response()->json(['data' => $managers]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'role'              => $user->role,
            'profile_image_url' => $user->profile_image_url,
            'is_active'         => $user->is_active,
            'created_by'        => $user->creator?->name,
            'created_at'        => $user->created_at->format('Y-m-d'),
        ];
    }
}