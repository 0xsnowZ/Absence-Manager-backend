<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stagiaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'programme_code'  => 'nullable|string',
        ]);

        $stagiaire = Stagiaire::create($request->except('programme_code'));

        if ($request->filled('programme_code')) {
            $programme = \App\Models\Programme::where('code_diplome', $request->programme_code)->first();
            if ($programme) {
                $stagiaire->programmes()->attach($programme->id);
            }
        }
        
        $stagiaire->load([
            'programmes:id,code_diplome,libelle_long,filiere_id',
            'programmes.filiere:id,code',
        ]);

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
            'programme_code' => 'nullable|string',
        ]);

        $stagiaire->update($request->except('programme_code'));

        if ($request->filled('programme_code')) {
            $programme = \App\Models\Programme::where('code_diplome', $request->programme_code)->first();
            if ($programme) {
                // Assuming sync to replace existing inscriptions or add if none
                $stagiaire->programmes()->sync([$programme->id]);
            }
        }
        
        $stagiaire->load([
            'programmes:id,code_diplome,libelle_long,filiere_id',
            'programmes.filiere:id,code',
        ]);

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
        $saison   = $request->query('saison');
        $classeId = $request->query('classe_id');

        // BUG-11: Use whereHas so the filter applies at the DB query level,
        // not only to the eager-loaded relation (which would leave null sessions).
        $query = $stagiaire->attendances()
            ->whereHas('session', fn($q) => $q->where('date_session', '>=', now()->subYear()))
            ->with(['session.programme', 'typeAbsence']);

        if ($classeId) {
            $query->whereHas('session', fn($q) => $q->where('classe_id', $classeId));
        }

        $attendances = $query->get();

        if ($saison) {
            $attendances = $attendances->filter(
                fn($a) => $a->session?->programme?->saison == $saison
            );
        }

        $totalSessions = $attendances->count();
        $presents      = $attendances->filter(fn($a) => $a->typeAbsence?->code === 'PRESENT')->count();
        $absents       = $attendances->filter(fn($a) => $a->typeAbsence?->code === 'ABSENT')->count();
        $justified     = $attendances->filter(fn($a) => $a->justification !== null)->count();

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

        // QA-02: Wrap in a transaction so a mid-import failure doesn't leave partial data
        DB::transaction(function () use ($validated, &$created, &$updated, &$errors) {
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
        });

        return response()->json([
            'success' => true,
            'message' => "Upsert complété: $created créés, $updated mis à jour, $errors erreurs",
            'data'    => ['created' => $created, 'updated' => $updated, 'errors' => $errors],
        ]);
    }
}
