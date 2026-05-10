<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stagiaire;
use Illuminate\Http\Request;

class StagiaireController extends Controller
{
    /**
     * Get all stagiaires
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $stagiaires = Stagiaire::paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $stagiaires,
        ]);
    }

    /**
     * Create a new stagiaire
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricule' => 'required|integer|unique:stagiaires',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'sexe' => 'nullable|string|max:1',
            'date_naissance' => 'nullable|date',
            'cin' => 'nullable|unique:stagiaires',
            'telephone' => 'nullable|string',
        ]);

        $stagiaire = Stagiaire::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stagiaire créé avec succès',
            'data' => $stagiaire,
        ], 201);
    }

    /**
     * Get a specific stagiaire
     */
    public function show(Stagiaire $stagiaire)
    {
        return response()->json([
            'success' => true,
            'data' => $stagiaire,
        ]);
    }

    /**
     * Update a stagiaire
     */
    public function update(Request $request, Stagiaire $stagiaire)
    {
        $validated = $request->validate([
            'matricule' => 'sometimes|integer|unique:stagiaires,matricule,' . $stagiaire->id,
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'sexe' => 'nullable|string|max:1',
            'date_naissance' => 'nullable|date',
            'cin' => 'nullable|unique:stagiaires,cin,' . $stagiaire->id,
            'telephone' => 'nullable|string',
        ]);

        $stagiaire->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stagiaire mis à jour avec succès',
            'data' => $stagiaire,
        ]);
    }

    /**
     * Delete a stagiaire
     */
    public function destroy(Stagiaire $stagiaire)
    {
        $stagiaire->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stagiaire supprimé avec succès',
        ]);
    }

    /**
     * Get programmes of a stagiaire
     */
    public function programmes(Stagiaire $stagiaire)
    {
        $programmes = $stagiaire->programmes()->get();

        return response()->json([
            'success' => true,
            'data' => $programmes,
        ]);
    }

    /**
     * Get attendance statistics for a stagiaire
     */
    public function attendanceStats(Stagiaire $stagiaire, Request $request)
    {
        $saison = $request->query('saison');
        $programmeId = $request->query('programme_id');

        $attendances = $stagiaire->attendances()
            ->with(['session' => function ($q) {
                $q->where('date_session', '>=', now()->subYear());
            }])
            ->get();

        if ($saison) {
            $attendances = $attendances->filter(function ($a) use ($saison) {
                return $a->session->programme->saison == $saison;
            });
        }

        if ($programmeId) {
            $attendances = $attendances->filter(function ($a) use ($programmeId) {
                return $a->session->programme_id == $programmeId;
            });
        }

        $totalSessions = $attendances->count();
        $presents = $attendances->filter(function ($a) {
            return $a->typeAbsence->code === 'PRESENT';
        })->count();
        $absents = $attendances->filter(function ($a) {
            return $a->typeAbsence->code === 'ABSENT';
        })->count();
        $justified = $attendances->filter(function ($a) {
            return !is_null($a->justification);
        })->count();

        $attendanceRate = $totalSessions > 0 ? ($presents / $totalSessions) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_sessions' => $totalSessions,
                'presents' => $presents,
                'absents' => $absents,
                'justified' => $justified,
                'attendance_rate' => round($attendanceRate, 2),
            ],
        ]);
    }

    /**
     * Upsert stagiaires from Excel
     */
    public function upsertFromExcel(Request $request)
    {
        $validated = $request->validate([
            'stagiaires' => 'required|array',
            'stagiaires.*.matricule' => 'required|integer',
            'stagiaires.*.nom' => 'required|string',
            'stagiaires.*.prenom' => 'required|string',
            'stagiaires.*.sexe' => 'nullable|string',
            'stagiaires.*.date_naissance' => 'nullable|date',
            'stagiaires.*.cin' => 'nullable|string',
            'stagiaires.*.telephone' => 'nullable|string',
        ]);

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($validated['stagiaires'] as $data) {
            try {
                $stagiaire = Stagiaire::updateOrCreate(
                    ['matricule' => $data['matricule']],
                    $data
                );

                if ($stagiaire->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Upsert complété: $created créés, $updated mis à jour, $errors erreurs",
            'data' => [
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
            ],
        ]);
    }
}
