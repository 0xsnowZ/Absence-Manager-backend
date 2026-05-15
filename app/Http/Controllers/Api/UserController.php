<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List users, optionally filtered by role.
     * GET /api/users?role=prof
     */
    public function index(Request $request)
    {
        $query = User::with(['programmes.filiere.secteur']);

        if ($request->has('role')) {
            $query->where('role', $request->query('role'));
        }

        // PERF-02: Add per_page support consistent with other controllers
        $perPage = (int) $request->query('per_page', 100);
        if ($perPage <= 0 || $perPage > 500) $perPage = 100;

        $users = $query->paginate($perPage);
        $formatted = $users->getCollection()->map(fn($u) => $this->formatUser($u));

        return response()->json([
            'success' => true,
            'data'    => $formatted,
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * Create a new user (admin or prof).
     * POST /api/users
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:8',  // SEC-01: raised from 6 to 8
            'role'           => ['required', Rule::in(['admin', 'prof'])],
            'programme_ids'  => 'nullable|array',
            'programme_ids.*'=> 'exists:classes,id',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        if (!empty($validated['programme_ids'])) {
            $user->programmes()->sync($validated['programme_ids']);
        }

        $user->load(['programmes.filiere.secteur']);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès.',
            'data'    => $this->formatUser($user),
        ], 201);
    }

    /**
     * Show a single user.
     * GET /api/users/{user}
     */
    public function show(User $user)
    {
        $user->load(['programmes.filiere.secteur']);

        return response()->json([
            'success' => true,
            'data'    => $this->formatUser($user),
        ]);
    }

    /**
     * Update a user.
     * PUT /api/users/{user}
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'email'          => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password'       => 'sometimes|string|min:8',  // SEC-01: raised from 6 to 8
            'role'           => ['sometimes', Rule::in(['admin', 'prof'])],
            'programme_ids'  => 'nullable|array',
            'programme_ids.*'=> 'exists:classes,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update(array_filter($validated, fn($k) => $k !== 'programme_ids', ARRAY_FILTER_USE_KEY));

        if (array_key_exists('programme_ids', $validated)) {
            $user->programmes()->sync($validated['programme_ids'] ?? []);
        }

        $user->load(['programmes.filiere.secteur']);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès.',
            'data'    => $this->formatUser($user),
        ]);
    }

    /**
     * Delete a user.
     * DELETE /api/users/{user}
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 403);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès.',
        ]);
    }

    /**
     * Format user for API response.
     */
    private function formatUser($user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'programmes'  => $user->programmes->map(fn ($programme) => [
                'id'           => $programme->id,
                'code_diplome' => $programme->code_diplome,
                'libelle_long' => $programme->libelle_long,
                'filiere'      => $programme->filiere ? [
                    'id'      => $programme->filiere->id,
                    'code'    => $programme->filiere->code,
                    'secteur' => $programme->filiere->secteur ? [
                        'id'   => $programme->filiere->secteur->id,
                        'code' => $programme->filiere->secteur->code,
                    ] : null,
                ] : null,
            ]),
        ];
    }
}
