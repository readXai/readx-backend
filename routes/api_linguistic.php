<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WordUnitController;
use App\Http\Controllers\Api\TextController;

/*
|--------------------------------------------------------------------------
| API Routes pour la structure linguistique
|--------------------------------------------------------------------------
|
| Routes spécialisées pour la gestion de la structure linguistique
| des textes arabes : mots, unités, racines, types linguistiques
|
*/

// Routes pour les unités de mots
Route::prefix('word-units')->group(function () {
    // Décomposer un mot en unités
    Route::post('/words/{wordId}/decompose', [WordUnitController::class, 'decomposeWord']);
    
    // Fusionner des unités d'un mot
    Route::post('/words/{wordId}/merge', [WordUnitController::class, 'mergeUnits']);
    
    // Assigner un type linguistique à une unité
    Route::put('/{unitId}/linguistic-type', [WordUnitController::class, 'assignLinguisticType']);
    
    // Assigner une racine à une unité
    Route::put('/{unitId}/root', [WordUnitController::class, 'assignRoot']);
    
    // Associer des images à une unité
    Route::put('/{unitId}/images', [WordUnitController::class, 'associateImages']);
    
    // Réassigner des images orphelines
    Route::post('/reassign-images', [WordUnitController::class, 'reassignOrphanedImages']);
});

// Routes pour les racines
Route::prefix('roots')->group(function () {
    // Lister toutes les racines
    Route::get('/', [WordUnitController::class, 'getRoots']);
});

// Routes pour les types linguistiques
Route::prefix('linguistic-types')->group(function () {
    // Obtenir tous les types disponibles
    Route::get('/', [WordUnitController::class, 'getLinguisticTypes']);
});

// Routes étendues pour les textes
Route::prefix('texts')->group(function () {
    // Analyser et créer la structure linguistique d'un texte
    Route::post('/{textId}/parse-structure', [TextController::class, 'parseTextStructure']);
    
    // Obtenir la structure complète d'un texte
    Route::get('/{textId}/structure', [TextController::class, 'getTextStructure']);
    
    // Obtenir les statistiques linguistiques d'un texte
    Route::get('/{textId}/statistics', [TextController::class, 'getTextStatistics']);
    
    // Vérifier si un texte est prêt pour la lecture
    Route::get('/{textId}/ready-check', [TextController::class, 'checkReadyForReading']);
    
    // Générer automatiquement les syllabes pour tous les mots d'un texte
    Route::post('/{textId}/generate-syllables', [TextController::class, 'generateSyllables']);
    
    // Obtenir les syllabes d'un mot spécifique
    Route::get('/{textId}/words/{wordId}/syllables', [TextController::class, 'getWordSyllables']);
    
    // Mettre à jour les syllabes d'un mot (correction manuelle)
    Route::put('/{textId}/words/{wordId}/syllables', [TextController::class, 'updateWordSyllables']);
});
