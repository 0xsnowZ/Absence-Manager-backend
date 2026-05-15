<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Get all attendance records
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $attendances = Attendance::with([
            'session',
            'stagiaire',
            'typeAbsence',
            'createdByUser:id,name,email',
            'updatedByUser:id,name,email'
        ])->paginate($perPage);

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
            'session_id' => 'required|exists:seances,id',
            'stagiaire_id' => 'required|exists:stagiaires,id',
            'type_absence_id' => 'required|exists:type_absences,id',
            'status' => 'sometimes|in:non_justifie,justifie,retard,absence_excusee',
            'justification' => 'nullable|string',
            'recorded_by' => 'nullable|string',
        ]);

        // Default status to 'non_justifie' if not provided
        $validated['status'] = $validated['status'] ?? 'non_justifie';
        $validated['recorded_at'] = now();

        $attendance = Attendance::create($validated);
        $attendance->load(['session', 'stagiaire', 'typeAbsence', 'createdByUser:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Absence enregistrée avec succès',
            'data' => $attendance,
        ], 201);
    }

    /**
     * Get a specific attendance record
     */
    public function show(Attendance $attendance)
    {
        $attendance->load([
            'session',
            'stagiaire',
            'typeAbsence',
            'createdByUser:id,name,email',
            'updatedByUser:id,name,email'
        ]);

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
            'session_id' => 'sometimes|exists:seances,id',
            'stagiaire_id' => 'sometimes|exists:stagiaires,id',
            'type_absence_id' => 'sometimes|exists:type_absences,id',
            'status' => 'sometimes|in:non_justifie,justifie,retard,absence_excusee',
            'justification' => 'nullable|string',
            'recorded_by' => 'nullable|string',
        ]);

        // If status is being changed to 'justifie', set justified_at
        if (isset($validated['status']) && $validated['status'] === 'justifie' && !$attendance->justified_at) {
            $validated['justified_at'] = now();
        }

        // If status is being changed away from 'justifie', clear justified_at
        if (isset($validated['status']) && $validated['status'] !== 'justifie') {
            $validated['justified_at'] = null;
        }

        $attendance->update($validated);
        $attendance->load(['session', 'stagiaire', 'typeAbsence', 'createdByUser:id,name,email', 'updatedByUser:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Absence mise à jour avec succès',
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
            'session_id' => 'required|exists:seances,id',
            'recorded_by' => 'nullable|string',
            'attendances' => 'required|array',
            'attendances.*.stagiaire_id' => 'required|exists:stagiaires,id',
            'attendances.*.type_absence_id' => 'required|exists:type_absences,id',
            'attendances.*.status' => 'sometimes|in:non_justifie,justifie,retard,absence_excusee',
            'attendances.*.justification' => 'nullable|string',
        ]);

        $created = [];
        $errors = [];

        foreach ($validated['attendances'] as $index => $data) {
            try {
                $data['status'] = $data['status'] ?? 'non_justifie';

                $attendance = Attendance::updateOrCreate(
                    [
                        'session_id' => $validated['session_id'],
                        'stagiaire_id' => $data['stagiaire_id'],
                    ],
                    [
                        'type_absence_id' => $data['type_absence_id'],
                        'status' => $data['status'],
                        'justification' => $data['justification'] ?? null,
                        'recorded_by' => $validated['recorded_by'] ?? null,
                        'recorded_at' => now(),
                    ]
                );
                $attendance->load(['session', 'stagiaire', 'typeAbsence', 'createdByUser:id,name,email']);
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
            'message' => count($created) . ' absences créées/mises à jour',
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
            ->with([
                'session' => function ($q) {
                    $q->with('programme');
                },
                'stagiaire',
                'typeAbsence',
                'createdByUser:id,name,email'
            ])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'count' => $attendances->total(),
            'data' => $attendances,
        ]);
    }

    /**
     * Update absence status (admin only)
     */
    public function updateStatus(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => 'required|in:non_justifie,justifie,retard,absence_excusee',
        ]);

        // If status is being changed to 'justifie', set justified_at
        if ($validated['status'] === 'justifie' && !$attendance->justified_at) {
            $validated['justified_at'] = now();
        }

        // If status is being changed away from 'justifie', clear justified_at
        if ($validated['status'] !== 'justifie') {
            $validated['justified_at'] = null;
        }

        $attendance->update($validated);
        $attendance->load(['session', 'stagiaire', 'typeAbsence', 'createdByUser:id,name,email', 'updatedByUser:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Statut d\'absence mis à jour avec succès',
            'data' => $attendance,
        ]);
    }

    /**
     * Get statistics by stagiaire
     * BUG-02: Replaced PHP-side groupBy with SQL aggregation to avoid loading all rows into memory.
     * BUG-06: Added session.programme eager load to prevent N+1 on saison filter.
     */
    public function statsByStagiaire(Request $request)
    {
        $programmeId = $request->query('programme_id');
        $saison      = $request->query('saison');

        // Build base query with SQL-level aggregation grouped by stagiaire
        $query = DB::table('attendances as a')
            ->join('stagiaires as s', 's.id', '=', 'a.stagiaire_id')
            ->join('seances as se', 'se.id', '=', 'a.session_id')
            ->join('type_absences as ta', 'ta.id', '=', 'a.type_absence_id')
            ->join('classes as c', 'c.id', '=', 'se.classe_id')
            ->select([
                'a.stagiaire_id',
                's.matricule',
                's.nom',
                's.prenom',
                DB::raw('COUNT(a.id) as total_sessions'),
                DB::raw("SUM(CASE WHEN ta.code = 'PRESENT' THEN 1 ELSE 0 END) as presents"),
                DB::raw("SUM(CASE WHEN ta.code = 'ABSENT' THEN 1 ELSE 0 END) as absents"),
                DB::raw("SUM(CASE WHEN a.justification IS NOT NULL THEN 1 ELSE 0 END) as justified"),
            ])
            ->groupBy('a.stagiaire_id', 's.matricule', 's.nom', 's.prenom');

        if ($programmeId) {
            $query->where('se.classe_id', $programmeId);
        }

        if ($saison) {
            $query->where('c.saison', $saison);
        }

        $results = $query->get();

        $stats = $results->map(function ($row) {
            $total = (int) $row->total_sessions;
            $presents = (int) $row->presents;
            return [
                'stagiaire_id'    => $row->stagiaire_id,
                'matricule'       => $row->matricule,
                'nom'             => $row->nom,
                'prenom'          => $row->prenom,
                'total_sessions'  => $total,
                'presents'        => $presents,
                'absents'         => (int) $row->absents,
                'justified'       => (int) $row->justified,
                'attendance_rate' => $total > 0 ? round(($presents / $total) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * Get statistics by programme
     * BUG-02: Replaced PHP-side groupBy with SQL aggregation.
     */
    public function statsByProgramme(Request $request)
    {
        $saison = $request->query('saison');

        $query = DB::table('attendances as a')
            ->join('seances as se', 'se.id', '=', 'a.session_id')
            ->join('classes as c', 'c.id', '=', 'se.classe_id')
            ->join('type_absences as ta', 'ta.id', '=', 'a.type_absence_id')
            ->select([
                'se.classe_id as programme_id',
                'c.code_diplome',
                DB::raw('COUNT(a.id) as total_records'),
                DB::raw("SUM(CASE WHEN ta.code = 'PRESENT' THEN 1 ELSE 0 END) as presents"),
                DB::raw("SUM(CASE WHEN ta.code = 'ABSENT' THEN 1 ELSE 0 END) as absents"),
            ])
            ->groupBy('se.classe_id', 'c.code_diplome');

        if ($saison) {
            $query->where('c.saison', $saison);
        }

        $results = $query->get();

        $stats = $results->map(function ($row) {
            $total    = (int) $row->total_records;
            $presents = (int) $row->presents;
            return [
                'programme_id'           => $row->programme_id,
                'code_diplome'           => $row->code_diplome,
                'total_records'          => $total,
                'presents'               => $presents,
                'absents'                => (int) $row->absents,
                'average_attendance_rate' => $total > 0 ? round(($presents / $total) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }
}
