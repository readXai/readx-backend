<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TextController;
use App\Http\Controllers\Api\WordController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\ReadingSessionController;
use App\Http\Controllers\Api\LevelController;
use App\Http\Controllers\Api\ClassroomController;

/*
|--------------------------------------------------------------------------
| API Routes pour l'application de lecture arabe
|--------------------------------------------------------------------------
|
| Routes spécifiques pour l'application de lecture arabe destinée aux
| élèves CE1-CM2. Toutes les routes sont préfixées par /api
|
*/

// Routes pour les niveaux scolaires
Route::prefix('levels')->group(function () {
    Route::get('/', [LevelController::class, 'index']);
    Route::get('/{level}', [LevelController::class, 'show']);
    Route::post('/', [LevelController::class, 'store']);
    Route::put('/{level}', [LevelController::class, 'update']);
    Route::delete('/{level}', [LevelController::class, 'destroy']);
    Route::post('/{level}/dissociate-classrooms', [LevelController::class, 'dissociateClassrooms']);
    Route::post('/{level}/dissociate-texts', [LevelController::class, 'dissociateTexts']);
});

// Routes pour les classes
Route::prefix('classrooms')->group(function () {
    Route::get('/', [ClassroomController::class, 'index']);
    Route::post('/', [ClassroomController::class, 'store']);
    Route::get('/{classroom}', [ClassroomController::class, 'show']);
    Route::put('/{classroom}', [ClassroomController::class, 'update']);
    Route::put('/{classroom}/assign-level', [ClassroomController::class, 'assignLevel']);
    Route::delete('/{classroom}', [ClassroomController::class, 'destroy']);
});

// Routes pour les élèves
Route::prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'index']);
    Route::post('/', [StudentController::class, 'store']);
    Route::get('/{id}', [StudentController::class, 'show']);
    Route::put('/{id}', [StudentController::class, 'update']);
    Route::delete('/{id}', [StudentController::class, 'destroy']);
});

// Routes pour les textes
Route::prefix('texts')->group(function () {
    Route::get('/', [TextController::class, 'index']);
    Route::post('/', [TextController::class, 'store']);
    Route::get('/{id}', [TextController::class, 'show']);
    Route::put('/{id}', [TextController::class, 'update']);
    Route::delete('/{id}', [TextController::class, 'destroy']);
});

// Routes pour les mots
Route::prefix('words')->group(function () {
    Route::get('/', [WordController::class, 'index']); // Nouvelle route pour lister tous les mots
    Route::post('/search-similar', [WordController::class, 'searchSimilar']);
    Route::get('/{id}', [WordController::class, 'show']);
    Route::get('/{id}/image-suggestions', [WordController::class, 'getImageSuggestions']);
    Route::get('/{id}/syllables', [WordController::class, 'getSyllables']);
    Route::get('/{id}/letters', [WordController::class, 'getLetters']);
    Route::get('/{id}/without-vocalization', [WordController::class, 'getWithoutVocalization']);
});

// Routes pour les images
Route::prefix('images')->group(function () {
    Route::get('/', [ImageController::class, 'index']);
    Route::post('/', [ImageController::class, 'store']);
    Route::get('/{id}', [ImageController::class, 'show']);
    Route::put('/{id}', [ImageController::class, 'update']);
    Route::delete('/{id}', [ImageController::class, 'destroy']);
    
    // Association/dissociation avec les mots
    Route::post('/attach-word', [ImageController::class, 'attachToWord']);
    Route::post('/detach-word', [ImageController::class, 'detachFromWord']);
    
    // Nouvelles routes pour la gestion des mots connectés
    Route::get('/{id}/words', [ImageController::class, 'getWords']);
    Route::post('/{id}/connect-words', [ImageController::class, 'connectWords']);
});

// Routes pour les interactions élève-mot
Route::prefix('interactions')->group(function () {
    Route::post('/', [InteractionController::class, 'store']);
    Route::post('/student-text', [InteractionController::class, 'getStudentTextInteractions']);
    Route::get('/student/{studentId}/stats', [InteractionController::class, 'getStudentStats']);
    Route::get('/student/{studentId}/familiar-words', [InteractionController::class, 'getFamiliarWords']);
    Route::post('/reset', [InteractionController::class, 'resetStudentTextInteractions']);
});

// Routes pour les sessions de lecture (chronomètre)
Route::prefix('reading-sessions')->group(function () {
    Route::post('/start', [ReadingSessionController::class, 'start']);
    Route::post('/{sessionId}/end', [ReadingSessionController::class, 'end']);
    Route::get('/student/{studentId}/active', [ReadingSessionController::class, 'getActiveSession']);
    Route::get('/student/{studentId}/history', [ReadingSessionController::class, 'getStudentSessions']);
    Route::put('/{sessionId}/stats', [ReadingSessionController::class, 'updateStats']);
});

// Route pour obtenir les informations utilisateur (optionnel)
Route::get('/user', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'API de lecture arabe fonctionnelle',
        'version' => '1.0.0'
    ]);
});
Route::get('/debug-images', function() { try { $controller = app('App\Http\Controllers\Api\ImageController'); return $controller->index(); } catch (Exception $e) { return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500); } });

// Inclure les routes pour la structure linguistique
require __DIR__.'/api_linguistic.php';
