<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    /**
     * Get all programmes
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $programmes = Programme::with(['filiere', 'niveau'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $programmes,
        ]);
    }

    /**
     * Create a new programme
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code_diplome' => 'required|string|unique:classes',
            'libelle_long' => 'nullable|string',
            'filiere_id' => 'required|exists:filieres,id',
            'niveau_id' => 'required|exists:niveau_formations,id',
            'annee' => 'nullable|integer',
            'saison' => 'nullable|integer',
            'is_cds' => 'nullable|boolean',
        ]);

        $programme = Programme::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Programme créé avec succès',
            'data' => $programme,
        ], 201);
    }

    /**
     * Get a specific programme
     */
    public function show(Programme $programme)
    {
        $programme->load(['filiere', 'niveau', 'sessions']);

        return response()->json([
            'success' => true,
            'data' => $programme,
        ]);
    }

    /**
     * Update a programme
     */
    public function update(Request $request, Programme $programme)
    {
        $validated = $request->validate([
            'code_diplome' => 'sometimes|string|unique:classes,code_diplome,' . $programme->id,
            'libelle_long' => 'nullable|string',
            'filiere_id' => 'sometimes|exists:filieres,id',
            'niveau_id' => 'sometimes|exists:niveau_formations,id',
            'annee' => 'nullable|integer',
            'saison' => 'nullable|integer',
            'is_cds' => 'nullable|boolean',
        ]);

        $programme->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Programme mis à jour avec succès',
            'data' => $programme,
        ]);
    }

    /**
     * Delete a programme
     */
    public function destroy(Programme $programme)
    {
        $programme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Programme supprimé avec succès',
        ]);
    }

    /**
     * Get programme by code_diplome
     */
    public function byCode($code)
    {
        $programme = Programme::where('code_diplome', $code)->with(['filiere', 'niveau'])->first();

        if (!$programme) {
            return response()->json([
                'success' => false,
                'message' => 'Programme non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $programme,
        ]);
    }

    /**
     * Get programme by libelle
     */
    public function byLibelle($libelle)
    {
        $programme = Programme::where('libelle_long', 'like', "%$libelle%")->with(['filiere', 'niveau'])->first();

        if (!$programme) {
            return response()->json([
                'success' => false,
                'message' => 'Programme non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $programme,
        ]);
    }

    /**
     * Get stagiaires of a programme
     */
    public function stagiaires(Programme $programme, Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $stagiaires = $programme->stagiaires()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $stagiaires,
        ]);
    }

    /**
     * Get sessions of a programme
     */
    public function sessions(Programme $programme, Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $sessions = $programme->sessions()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Get attendance summary for a programme
     */
    public function attendanceSummary(Programme $programme)
    {
        $sessions = $programme->sessions()->count();
        $stagiaires = $programme->stagiaires()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'programme_id' => $programme->id,
                'code_diplome' => $programme->code_diplome,
                'total_sessions' => $sessions,
                'total_stagiaires' => $stagiaires,
            ],
        ]);
    }

    /**
     * Create multiple sessions for a programme
     */
    public function createMultipleSessions(Request $request, Programme $programme)
    {
        $validated = $request->validate([
            'sessions' => 'required|array',
            'sessions.*.date_session' => 'required|date',
            'sessions.*.heure_debut' => 'nullable|date_format:H:i',
            'sessions.*.heure_fin' => 'nullable|date_format:H:i',
            'sessions.*.lieu' => 'nullable|string',
            'sessions.*.created_by' => 'nullable|string',
        ]);

        $created = [];
        foreach ($validated['sessions'] as $data) {
            $data['classe_id'] = $programme->id;
            $session = $programme->sessions()->create($data);
            $created[] = $session;
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' sessions créées avec succès',
            'data' => $created,
        ], 201);
    }
}
