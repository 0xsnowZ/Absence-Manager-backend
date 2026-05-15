<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeAbsence;

/**
 * BUG-04: Expose type_absences so the frontend can look up 'ABSENT' id dynamically
 * instead of hardcoding id=2.
 */
class TypeAbsenceController extends Controller
{
    /**
     * GET /api/type-absences
     * Returns all absence types (small static table, no pagination needed).
     */
    public function index()
    {
        $types = TypeAbsence::orderBy('code')->get();

        return response()->json([
            'success' => true,
            'data'    => $types,
        ]);
    }
}
