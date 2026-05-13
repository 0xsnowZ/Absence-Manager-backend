<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ProgrammeController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StagiaireController;
use App\Http\Controllers\Api\TimeBlockController;
use App\Http\Controllers\Api\UserController;

// ─── Public routes ────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::delete('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Time blocks (read-only)
    Route::get('/time-blocks', [TimeBlockController::class, 'index']);

    // Users (admin only in practice — enforce in controller)
    Route::apiResource('users', UserController::class);

    // Stagiaires
    Route::apiResource('stagiaires', StagiaireController::class);
    Route::get('stagiaires/{stagiaire}/programmes', [StagiaireController::class, 'programmes']);
    Route::get('stagiaires/{stagiaire}/attendance-stats', [StagiaireController::class, 'attendanceStats']);
    Route::post('stagiaires/upsert-from-excel', [StagiaireController::class, 'upsertFromExcel']);

    // Programmes
    Route::apiResource('programmes', ProgrammeController::class);
    Route::get('programmes/by-code/{code}', [ProgrammeController::class, 'byCode']);
    Route::get('programmes/by-libelle/{libelle}', [ProgrammeController::class, 'byLibelle']);
    Route::get('programmes/{programme}/stagiaires', [ProgrammeController::class, 'stagiaires']);
    Route::get('programmes/{programme}/sessions', [ProgrammeController::class, 'sessions']);
    Route::get('programmes/{programme}/attendance-summary', [ProgrammeController::class, 'attendanceSummary']);
    Route::post('programmes/{programme}/sessions/create-multiple', [ProgrammeController::class, 'createMultipleSessions']);

    // Sessions
    Route::apiResource('sessions', SessionController::class);
    Route::get('sessions/{session}/summary', [SessionController::class, 'summary']);
    Route::get('sessions/{session}/roster', [SessionController::class, 'roster']);
    Route::get('sessions/programme/{code}/upcoming', [SessionController::class, 'upcomingByProgramme']);

    // Attendances
    Route::apiResource('attendances', AttendanceController::class);
    Route::post('attendances/bulk', [AttendanceController::class, 'bulk']);
    Route::get('attendances/unjustified/list', [AttendanceController::class, 'unjustifiedList']);
    Route::get('attendances/stats/by-stagiaire', [AttendanceController::class, 'statsByStagiaire']);
    Route::get('attendances/stats/by-programme', [AttendanceController::class, 'statsByProgramme']);
});
