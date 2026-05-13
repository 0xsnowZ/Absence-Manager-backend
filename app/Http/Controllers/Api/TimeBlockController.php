<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeBlock;

class TimeBlockController extends Controller
{
    /**
     * Return all time blocks.
     * GET /api/time-blocks
     */
    public function index()
    {
        $blocks = TimeBlock::orderBy('heure_debut')->get();

        return response()->json([
            'success' => true,
            'data'    => $blocks,
        ]);
    }
}
