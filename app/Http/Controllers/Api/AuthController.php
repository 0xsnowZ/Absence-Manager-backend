<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login — issue a Sanctum token.
     *
     * POST /api/login
     * Body: { email, password }
     * Returns: { token, user: { id, name, email, role, programmes[] } }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('programmes');

        // Revoke previous tokens for this device (optional: keep multi-device by removing this)
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'programmes'  => $user->programmes->map(fn($p) => [
                    'id'           => $p->id,
                    'code_diplome' => $p->code_diplome,
                    'libelle_long' => $p->libelle_long,
                ]),
            ],
        ]);
    }

    /**
     * Logout — revoke the current token.
     *
     * DELETE /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * Return the authenticated user.
     *
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('programmes');

        return response()->json([
            'success' => true,
            'user'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'programmes' => $user->programmes->map(fn($p) => [
                    'id'           => $p->id,
                    'code_diplome' => $p->code_diplome,
                    'libelle_long' => $p->libelle_long,
                ]),
            ],
        ]);
    }
}
