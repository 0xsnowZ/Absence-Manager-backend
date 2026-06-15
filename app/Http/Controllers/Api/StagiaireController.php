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
        ])->orderBy('nom')->orderBy('prenom')->paginate($perPage);

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
        $stagiaire->load([
            'programmes:id,code_diplome,libelle_long,filiere_id',
            'programmes.filiere:id,code',
        ]);

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
        $stagiaires = $request->input('stagiaires', []);

        if (!is_array($stagiaires) || empty($stagiaires)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune donnée reçue.',
            ], 422);
        }

        $created = 0;
        $updated = 0;
        $errors  = 0;

        foreach ($stagiaires as $data) {
            try {
                if (!isset($data['matricule']) || !isset($data['nom']) || !isset($data['prenom'])) {
                    $errors++;
                    continue;
                }

                $codeDiplome = $data['code_diplome'] ?? null;
                $dateInscription = $data['date_inscription'] ?? null;
                $dateDossierComplet = $data['date_dossier_complet'] ?? null;

                unset($data['code_diplome'], $data['date_inscription'], $data['date_dossier_complet']);

                // Nullify empty strings or invalid date-like values
                foreach (['date_naissance', 'date_inscription', 'date_dossier_complet'] as $f) {
                    if (isset($data[$f]) && ($data[$f] === '' || $data[$f] === 'null' || $data[$f] === 'Invalid Date')) {
                        $data[$f] = null;
                    }
                }

                $stagiaire = Stagiaire::updateOrCreate(
                    ['matricule' => $data['matricule']],
                    $data
                );

                if ($codeDiplome) {
                    $programme = \App\Models\Programme::where('code_diplome', $codeDiplome)->first();
                    if ($programme) {
                        $stagiaire->programmes()->syncWithoutDetaching([$programme->id]);

                        // Sync inscription dates if provided
                        $pivotData = [];
                        if ($dateInscription) $pivotData['date_inscription'] = $dateInscription;
                        if ($dateDossierComplet) $pivotData['date_dossier_complet'] = $dateDossierComplet;
                        if (!empty($pivotData)) {
                            $stagiaire->inscriptions()
                                ->where('classe_id', $programme->id)
                                ->update($pivotData);
                        }
                    }
                }

                $stagiaire->wasRecentlyCreated ? $created++ : $updated++;
            } catch (\Exception $e) {
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
