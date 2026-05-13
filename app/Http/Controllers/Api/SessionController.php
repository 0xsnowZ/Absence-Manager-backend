<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Programme;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Get all sessions
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $sessions = Session::with(['programme'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Create a new session
     */
    public function store(Request $request)
    {
        // Prefer `time_block_id` over raw times. Keep heure_debut/heure_fin optional for migration safety.
        $validated = $request->validate([
            'classe_id'    => 'required|exists:classes,id',
            'date_session' => 'required|date',
            'time_block_id' => 'nullable|exists:time_blocks,id',
            'heure_debut'  => 'nullable|date_format:H:i',
            'heure_fin'    => 'nullable|date_format:H:i',
            'lieu'         => 'nullable|string',
            'created_by'   => 'nullable|string',
        ]);

        $session = Session::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Session créée avec succès',
            'data' => $session,
        ], 201);
    }

    /**
     * Get a specific session
     */
    public function show(Session $session)
    {
        $session->load(['programme', 'attendances']);

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    /**
     * Update a session
     */
    public function update(Request $request, Session $session)
    {
        $validated = $request->validate([
            'classe_id'    => 'sometimes|exists:classes,id',
            'date_session' => 'sometimes|date',
            'time_block_id' => 'nullable|exists:time_blocks,id',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i',
            'lieu' => 'nullable|string',
            'created_by' => 'nullable|string',
        ]);

        $session->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Session mise à jour avec succès',
            'data' => $session,
        ]);
    }

    /**
     * Delete a session
     */
    public function destroy(Session $session)
    {
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session supprimée avec succès',
        ]);
    }

    /**
     * Get session summary
     */
    public function summary(Session $session)
    {
        $attendances = $session->attendances()->with(['stagiaire', 'typeAbsence'])->get();

        $stats = [
            'total_marked' => $attendances->count(),
            'presents' => $attendances->filter(fn($a) => $a->typeAbsence->code === 'PRESENT')->count(),
            'absents' => $attendances->filter(fn($a) => $a->typeAbsence->code === 'ABSENT')->count(),
            'excused' => $attendances->filter(fn($a) => $a->typeAbsence->code === 'EXCUSED')->count(),
            'sick' => $attendances->filter(fn($a) => $a->typeAbsence->code === 'SICK')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => array_merge([
                'session_id' => $session->id,
                'date_session' => $session->date_session,
                'programme' => $session->programme->code_diplome,
            ], $stats),
        ]);
    }

    /**
     * Get session roster (all enrolled stagiaires with attendance)
     */
    public function roster(Session $session)
    {
        $programme = $session->programme;
        $enrolledStagiaires = $programme->stagiaires()->get();
        $attendances = $session->attendances()->with(['stagiaire', 'typeAbsence'])->get();

        $roster = [];
        foreach ($enrolledStagiaires as $stagiaire) {
            $attendance = $attendances->firstWhere('stagiaire_id', $stagiaire->id);

            $roster[] = [
                'stagiaire_id' => $stagiaire->id,
                'matricule' => $stagiaire->matricule,
                'nom' => $stagiaire->nom,
                'prenom' => $stagiaire->prenom,
                'telephone' => $stagiaire->telephone,
                'attendance_id' => $attendance?->id,
                'absence_code' => $attendance?->typeAbsence->code,
                'absence_libelle' => $attendance?->typeAbsence->libelle,
                'justification' => $attendance?->justification,
                'recorded_by' => $attendance?->recorded_by,
                'recorded_at' => $attendance?->recorded_at,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => [
                    'id' => $session->id,
                    'date' => $session->date_session,
                    'heure_debut' => $session->heure_debut,
                    'heure_fin' => $session->heure_fin,
                    'programme' => $programme->code_diplome,
                ],
                'total_enrolled' => $enrolledStagiaires->count(),
                'total_marked' => $attendances->count(),
                'roster' => $roster,
            ],
        ]);
    }

    /**
     * Get upcoming sessions for a programme
     */
    public function upcomingByProgramme($code)
    {
        $programme = Programme::where('code_diplome', $code)->first();

        if (!$programme) {
            return response()->json([
                'success' => false,
                'message' => 'Programme non trouvé',
            ], 404);
        }

        $sessions = $programme->sessions()->future()->orderBy('date_session')->get();

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }
}
