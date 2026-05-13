<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Secteur;
use Illuminate\Http\JsonResponse;

class SecteurController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => Secteur::withCount(['filieres', 'programmes'])->get(),
        ]);
    }

    public function filieres(Secteur $secteur): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $secteur->filieres()->get(),
        ]);
    }

    public function programmes(Secteur $secteur): JsonResponse
    {
        $programmes = $secteur->filieres()
            ->with(['programmes' => function ($q) {
                $q->withCount('inscriptions');
            }])
            ->get()
            ->pluck('programmes')
            ->flatten()
            ->sortBy('code_diplome')
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $programmes,
        ]);
    }
}
