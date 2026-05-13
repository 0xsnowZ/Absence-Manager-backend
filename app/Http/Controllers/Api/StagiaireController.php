<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stagiaire;
use Illuminate\Http\Request;

class StagiaireController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 200), 500);
        $stagiaires = Stagiaire::with([
            'programmes:id,code_diplome,libelle_long,filiere_id',
            'programmes.filiere:id,code',
        ])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $stagiaires,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricule'       => 'required|numeric|unique:stagiaires',
            'nom'             => 'required|string',
            'prenom'          => 'required|string',
            'sexe'            => 'nullable|string|max:1',
            'date_naissance'  => 'nullable|date',
            'lieu_naissance'  => 'nullable|string',
            'cin'             => 'nullable|unique:stagiaires',
            'telephone'       => 'nullable|string',
        ]);

        $stagiaire = Stagiaire::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stagiaire créé avec succès',
            'data'    => $stagiaire,
        ], 201);
    }

    public function show(Stagiaire $stagiaire)
    {
        return response()->json([
            'success' => true,
            'data'    => $stagiaire,
        ]);
    }

    public function update(Request $request, Stagiaire $stagiaire)
    {
        $validated = $request->validate([
            'matricule'      => "sometimes|numeric|unique:stagiaires,matricule,{$stagiaire->id}",
            'nom'            => 'sometimes|string',
            'prenom'         => 'sometimes|string',
            'sexe'           => 'nullable|string|max:1',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string',
            'cin'            => "nullable|unique:stagiaires,cin,{$stagiaire->id}",
            'telephone'      => 'nullable|string',
        ]);

        $stagiaire->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stagiaire mis à jour avec succès',
            'data'    => $stagiaire,
        ]);
    }

    public function destroy(Stagiaire $stagiaire)
    {
        $stagiaire->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stagiaire supprimé avec succès',
        ]);
    }

    public function programmes(Stagiaire $stagiaire)
    {
        return response()->json([
            'success' => true,
            'data'    => $stagiaire->programmes()->get(),
        ]);
    }

    public function attendanceStats(Stagiaire $stagiaire, Request $request)
    {
        $saison      = $request->query('saison');
        $classeId = $request->query('classe_id');

        $attendances = $stagiaire->attendances()
            ->with(['session' => fn ($q) => $q->where('date_session', '>=', now()->subYear())])
            ->get();

        if ($saison) {
            $attendances = $attendances->filter(
                fn ($a) => $a->session->programme->saison == $saison
            );
        }

        if ($classeId) {
            $attendances = $attendances->filter(
                fn ($a) => $a->session->classe_id == $classeId
            );
        }

        $totalSessions = $attendances->count();
        $presents      = $attendances->filter(fn ($a) => $a->typeAbsence->code === 'PRESENT')->count();
        $absents       = $attendances->filter(fn ($a) => $a->typeAbsence->code === 'ABSENT')->count();
        $justified     = $attendances->filter(fn ($a) => $a->justification !== null)->count();

        $attendanceRate = $totalSessions > 0 ? ($presents / $totalSessions) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_sessions'  => $totalSessions,
                'presents'        => $presents,
                'absents'         => $absents,
                'justified'       => $justified,
                'attendance_rate' => round($attendanceRate, 2),
            ],
        ]);
    }

    public function upsertFromExcel(Request $request)
    {
        $validated = $request->validate([
            'stagiaires'                   => 'required|array',
            'stagiaires.*.matricule'       => 'required|numeric',
            'stagiaires.*.nom'             => 'required|string',
            'stagiaires.*.prenom'          => 'required|string',
            'stagiaires.*.sexe'            => 'nullable|string',
            'stagiaires.*.date_naissance'  => 'nullable|date',
            'stagiaires.*.lieu_naissance'  => 'nullable|string',
            'stagiaires.*.cin'             => 'nullable|string',
            'stagiaires.*.telephone'       => 'nullable|string',
        ]);

        $created = 0;
        $updated = 0;
        $errors  = 0;

        foreach ($validated['stagiaires'] as $data) {
            try {
                $stagiaire = Stagiaire::updateOrCreate(
                    ['matricule' => $data['matricule']],
                    $data
                );
                $stagiaire->wasRecentlyCreated ? $created++ : $updated++;
            } catch (\Exception) {
                $errors++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Upsert complété: $created créés, $updated mis à jour, $errors erreurs",
            'data'    => ['created' => $created, 'updated' => $updated, 'errors' => $errors],
        ]);
    }
}
