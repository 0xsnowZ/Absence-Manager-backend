<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ProgrammeController;
use App\Http\Controllers\Api\SecteurController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StagiaireController;
use App\Http\Controllers\Api\TimeBlockController;
use App\Http\Controllers\Api\TypeAbsenceController;
use App\Http\Controllers\Api\UserController;

// ─── Public routes ────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::delete('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Time blocks (read-only, available to all roles)
    Route::get('/time-blocks', [TimeBlockController::class, 'index']);

    // Type absences — BUG-04: exposed so frontend can resolve 'ABSENT' id dynamically
    Route::get('/type-absences', [TypeAbsenceController::class, 'index']);

    // Secteurs (read-only, available to all roles)
    Route::get('secteurs', [SecteurController::class, 'index']);
    Route::get('secteurs/{secteur}/filieres', [SecteurController::class, 'filieres']);
    Route::get('secteurs/{secteur}/programmes', [SecteurController::class, 'programmes']);

    // Stagiaires — reads available to all; writes restricted to admin
    Route::get('stagiaires', [StagiaireController::class, 'index']);
    Route::get('stagiaires/{stagiaire}', [StagiaireController::class, 'show']);
    Route::get('stagiaires/{stagiaire}/programmes', [StagiaireController::class, 'programmes']);
    Route::get('stagiaires/{stagiaire}/attendance-stats', [StagiaireController::class, 'attendanceStats']);
    Route::middleware('role:admin')->group(function () {
        Route::post('stagiaires', [StagiaireController::class, 'store']);
        Route::put('stagiaires/{stagiaire}', [StagiaireController::class, 'update']);
        Route::delete('stagiaires/{stagiaire}', [StagiaireController::class, 'destroy']);
        Route::post('stagiaires/upsert-from-excel', [StagiaireController::class, 'upsertFromExcel']);
    });

    // Programmes — reads available to all; writes restricted to admin
    Route::get('programmes', [ProgrammeController::class, 'index']);
    Route::get('programmes/by-code/{code}', [ProgrammeController::class, 'byCode']);
    Route::get('programmes/by-libelle/{libelle}', [ProgrammeController::class, 'byLibelle']);
    Route::get('programmes/{programme}', [ProgrammeController::class, 'show']);
    Route::get('programmes/{programme}/stagiaires', [ProgrammeController::class, 'stagiaires']);
    Route::get('programmes/{programme}/sessions', [ProgrammeController::class, 'sessions']);
    Route::get('programmes/{programme}/attendance-summary', [ProgrammeController::class, 'attendanceSummary']);
    Route::middleware('role:admin')->group(function () {
        Route::post('programmes', [ProgrammeController::class, 'store']);
        Route::put('programmes/{programme}', [ProgrammeController::class, 'update']);
        Route::delete('programmes/{programme}', [ProgrammeController::class, 'destroy']);
        Route::post('programmes/{programme}/sessions/create-multiple', [ProgrammeController::class, 'createMultipleSessions']);
    });

    // Sessions — BUG-01: find-or-create registered before {session} wildcard to avoid routing clash
    Route::post('sessions/find-or-create', [SessionController::class, 'findOrCreate']);
    Route::get('sessions/programme/{code}/upcoming', [SessionController::class, 'upcomingByProgramme']);
    Route::get('sessions', [SessionController::class, 'index']);
    Route::post('sessions', [SessionController::class, 'store']);
    Route::get('sessions/{session}', [SessionController::class, 'show']);
    Route::put('sessions/{session}', [SessionController::class, 'update']);
    Route::get('sessions/{session}/summary', [SessionController::class, 'summary']);
    Route::get('sessions/{session}/roster', [SessionController::class, 'roster']);
    Route::middleware('role:admin')->group(function () {
        Route::delete('sessions/{session}', [SessionController::class, 'destroy']);
    });

    // Attendances — profs can create/read; admins can update status and delete
    // Static paths registered BEFORE dynamic {attendance} wildcard
    Route::get('attendances/unjustified/list', [AttendanceController::class, 'unjustifiedList']);
    Route::get('attendances/stats/by-stagiaire', [AttendanceController::class, 'statsByStagiaire']);
    Route::get('attendances/stats/by-programme', [AttendanceController::class, 'statsByProgramme']);
    Route::post('attendances/bulk', [AttendanceController::class, 'bulk']);
    Route::get('attendances', [AttendanceController::class, 'index']);
    Route::post('attendances', [AttendanceController::class, 'store']);
    Route::get('attendances/{attendance}', [AttendanceController::class, 'show']);
    Route::put('attendances/{attendance}', [AttendanceController::class, 'update']);
    Route::middleware('role:admin')->group(function () {
        Route::patch('attendances/{attendance}/status', [AttendanceController::class, 'updateStatus']);
        Route::delete('attendances/{attendance}', [AttendanceController::class, 'destroy']);
    });

    // Users — BUG-05: admin only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
