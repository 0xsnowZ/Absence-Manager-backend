<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Get all attendance records
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $attendances = Attendance::with(['session', 'stagiaire', 'typeAbsence'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $attendances,
        ]);
    }

    /**
     * Create a new attendance record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'stagiaire_id' => 'required|exists:stagiaires,id',
            'type_absence_id' => 'required|exists:type_absences,id',
            'justification' => 'nullable|string',
            'recorded_by' => 'nullable|string',
        ]);

        $validated['recorded_at'] = now();

        $attendance = Attendance::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Présence enregistrée avec succès',
            'data' => $attendance,
        ], 201);
    }

    /**
     * Get a specific attendance record
     */
    public function show(Attendance $attendance)
    {
        $attendance->load(['session', 'stagiaire', 'typeAbsence']);

        return response()->json([
            'success' => true,
            'data' => $attendance,
        ]);
    }

    /**
     * Update an attendance record
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'session_id' => 'sometimes|exists:sessions,id',
            'stagiaire_id' => 'sometimes|exists:stagiaires,id',
            'type_absence_id' => 'sometimes|exists:type_absences,id',
            'justification' => 'nullable|string',
            'recorded_by' => 'nullable|string',
        ]);

        $attendance->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Présence mise à jour avec succès',
            'data' => $attendance,
        ]);
    }

    /**
     * Delete an attendance record
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enregistrement supprimé avec succès',
        ]);
    }

    /**
     * Record multiple attendances for a session (bulk)
     */
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'recorded_by' => 'nullable|string',
            'attendances' => 'required|array',
            'attendances.*.stagiaire_id' => 'required|exists:stagiaires,id',
            'attendances.*.type_absence_id' => 'required|exists:type_absences,id',
            'attendances.*.justification' => 'nullable|string',
        ]);

        $created = [];
        $errors = [];

        foreach ($validated['attendances'] as $index => $data) {
            try {
                $attendance = Attendance::updateOrCreate(
                    [
                        'session_id' => $validated['session_id'],
                        'stagiaire_id' => $data['stagiaire_id'],
                    ],
                    [
                        'type_absence_id' => $data['type_absence_id'],
                        'justification' => $data['justification'] ?? null,
                        'recorded_by' => $validated['recorded_by'] ?? null,
                        'recorded_at' => now(),
                    ]
                );
                $created[] = $attendance;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'message' => count($created) . ' enregistrements créés/mis à jour',
            'data' => [
                'created_count' => count($created),
                'error_count' => count($errors),
                'errors' => $errors,
                'attendances' => $created,
            ],
        ], count($errors) > 0 ? 207 : 201);
    }

    /**
     * Get unjustified absences (alerts)
     */
    public function unjustifiedList(Request $request)
    {
        $daysBack = $request->query('days_back', 7);
        $perPage = $request->query('per_page', 15);

        $cutoffDate = now()->subDays($daysBack)->toDateString();

        $attendances = Attendance::unjustified()
            ->whereHas('session', function ($q) use ($cutoffDate) {
                $q->where('date_session', '>=', $cutoffDate);
            })
            ->with(['session' => function ($q) {
                $q->with('programme');
            }, 'stagiaire', 'typeAbsence'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'count' => $attendances->total(),
            'data' => $attendances,
        ]);
    }

    /**
     * Get statistics by stagiaire
     */
    public function statsByStagiaire(Request $request)
    {
        $programmeId = $request->query('programme_id');
        $saison = $request->query('saison');

        $query = Attendance::with(['stagiaire', 'typeAbsence', 'session']);

        if ($programmeId) {
            $query->whereHas('session', function ($q) use ($programmeId) {
                $q->where('programme_id', $programmeId);
            });
        }

        $attendances = $query->get();

        $stats = [];
        foreach ($attendances->groupBy('stagiaire_id') as $stagiaireId => $records) {
            $stagiaire = $records->first()->stagiaire;

            $filtered = $records;
            if ($saison) {
                $filtered = $records->filter(function ($a) use ($saison) {
                    return $a->session->programme->saison == $saison;
                });
            }

            if ($filtered->isEmpty()) continue;

            $stats[] = [
                'stagiaire_id' => $stagiaireId,
                'matricule' => $stagiaire->matricule,
                'nom' => $stagiaire->nom,
                'prenom' => $stagiaire->prenom,
                'total_sessions' => $filtered->count(),
                'presents' => $filtered->filter(fn($a) => $a->typeAbsence->code === 'PRESENT')->count(),
                'absents' => $filtered->filter(fn($a) => $a->typeAbsence->code === 'ABSENT')->count(),
                'justified' => $filtered->filter(fn($a) => !is_null($a->justification))->count(),
                'attendance_rate' => round(($filtered->filter(fn($a) => $a->typeAbsence->code === 'PRESENT')->count() / $filtered->count()) * 100, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get statistics by programme
     */
    public function statsByProgramme(Request $request)
    {
        $saison = $request->query('saison');

        $query = Attendance::with(['session', 'typeAbsence'])
            ->whereHas('session');

        $attendances = $query->get();

        $stats = [];
        foreach ($attendances->groupBy(function ($a) { return $a->session->programme_id; }) as $programmeId => $records) {
            $programme = $records->first()->session->programme;

            $filtered = $records;
            if ($saison) {
                $filtered = $records->filter(function ($a) use ($saison) {
                    return $a->session->programme->saison == $saison;
                });
            }

            if ($filtered->isEmpty()) continue;

            $stats[] = [
                'programme_id' => $programmeId,
                'code_diplome' => $programme->code_diplome,
                'total_records' => $filtered->count(),
                'presents' => $filtered->filter(fn($a) => $a->typeAbsence->code === 'PRESENT')->count(),
                'absents' => $filtered->filter(fn($a) => $a->typeAbsence->code === 'ABSENT')->count(),
                'average_attendance_rate' => round(($filtered->filter(fn($a) => $a->typeAbsence->code === 'PRESENT')->count() / $filtered->count()) * 100, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
