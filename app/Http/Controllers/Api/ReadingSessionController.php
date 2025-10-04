<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReadingSessionController extends Controller
{
    /**
     * Démarrer une nouvelle session de lecture
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'text_id' => 'sometimes|exists:texts,id'
        ]);

        // Vérifier s'il y a déjà une session active pour cet élève
        $activeSession = ReadingSession::where('student_id', $validated['student_id'])
                                     ->whereNull('end_time')
                                     ->first();

        if ($activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Une session est déjà active pour cet élève',
                'data' => [
                    'active_session' => $activeSession,
                    'formatted_duration' => $activeSession->formatted_duration
                ]
            ], 409);
        }

        $session = ReadingSession::startSession(
            $validated['student_id'],
            $validated['text_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Session de lecture démarrée',
            'data' => $session
        ], 201);
    }

    /**
     * Terminer une session de lecture
     */
    public function end(string $sessionId): JsonResponse
    {
        $session = ReadingSession::find($sessionId);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée'
            ], 404);
        }

        if (!$session->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette session est déjà terminée'
            ], 400);
        }

        $session->endSession();

        return response()->json([
            'success' => true,
            'message' => 'Session terminée avec succès',
            'data' => [
                'session' => $session,
                'duration_seconds' => $session->duration,
                'formatted_duration' => $session->formatted_duration
            ]
        ]);
    }

    /**
     * Obtenir la session active d'un élève
     */
    public function getActiveSession(string $studentId): JsonResponse
    {
        $student = Student::find($studentId);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        }

        $activeSession = ReadingSession::where('student_id', $studentId)
                                     ->whereNull('end_time')
                                     ->with(['student', 'text'])
                                     ->first();

        if (!$activeSession) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune session active',
                'data' => null
            ]);
        }

        // Calculer la durée actuelle
        $currentDuration = $activeSession->start_time->diffInSeconds(Carbon::now());

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $activeSession,
                'current_duration_seconds' => $currentDuration,
                'current_formatted_duration' => sprintf('%02d:%02d', 
                    floor($currentDuration / 60), 
                    $currentDuration % 60
                )
            ]
        ]);
    }

    /**
     * Obtenir l'historique des sessions d'un élève
     */
    public function getStudentSessions(string $studentId): JsonResponse
    {
        $student = Student::find($studentId);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Élève non trouvé'
            ], 404);
        }

        $sessions = ReadingSession::where('student_id', $studentId)
                                 ->with(['text'])
                                 ->orderByDesc('start_time')
                                 ->get()
                                 ->map(function ($session) {
                                     return [
                                         'id' => $session->id,
                                         'text_title' => $session->text ? $session->text->title : 'Session libre',
                                         'start_time' => $session->start_time,
                                         'end_time' => $session->end_time,
                                         'duration_seconds' => $session->duration,
                                         'formatted_duration' => $session->formatted_duration,
                                         'words_read' => $session->words_read,
                                         'help_requested' => $session->help_requested,
                                         'is_active' => $session->isActive()
                                     ];
                                 });

        $totalTime = $sessions->where('duration_seconds', '!=', null)->sum('duration_seconds');
        $totalSessions = $sessions->count();
        $averageTime = $totalSessions > 0 ? round($totalTime / $totalSessions) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'sessions' => $sessions,
                'statistics' => [
                    'total_sessions' => $totalSessions,
                    'total_time_seconds' => $totalTime,
                    'average_time_seconds' => $averageTime,
                    'total_formatted_time' => sprintf('%02d:%02d', 
                        floor($totalTime / 60), 
                        $totalTime % 60
                    ),
                    'average_formatted_time' => sprintf('%02d:%02d', 
                        floor($averageTime / 60), 
                        $averageTime % 60
                    )
                ]
            ]
        ]);
    }

    /**
     * Mettre à jour les statistiques d'une session (mots lus, aides demandées)
     */
    public function updateStats(Request $request, string $sessionId): JsonResponse
    {
        $session = ReadingSession::find($sessionId);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée'
            ], 404);
        }

        $validated = $request->validate([
            'words_read' => 'sometimes|integer|min:0',
            'help_requested' => 'sometimes|integer|min:0'
        ]);

        $session->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Statistiques mises à jour',
            'data' => $session
        ]);
    }
}
