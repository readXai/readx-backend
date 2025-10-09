-- =====================================================
-- ReadX Analytics Dashboard - PostgreSQL Views
-- Tableaux de bord pédagogiques pour le suivi des élèves
-- =====================================================

-- 1. VUE ANALYTICS ÉLÈVES (corrigée selon la structure réelle)
-- =====================================================

CREATE OR REPLACE VIEW student_progress_analytics AS
SELECT 
    s.id as student_id,
    s.name as student_name,
    c.name as classroom_name,
    l.name as level_name,
    
    -- Statistiques d'interactions
    COUNT(DISTINCT si.id) as total_interactions,
    COUNT(DISTINCT si.word_id) as unique_words_interacted,
    COUNT(DISTINCT si.text_id) as texts_attempted,
    
    -- Analyse des types d'actions
    COUNT(CASE WHEN si.action_type = 'click' THEN 1 END) as simple_clicks,
    COUNT(CASE WHEN si.action_type = 'double_click' THEN 1 END) as double_clicks,
    COUNT(CASE WHEN si.action_type = 'help_syllables' THEN 1 END) as syllable_helps,
    COUNT(CASE WHEN si.action_type = 'help_letters' THEN 1 END) as letter_helps,
    COUNT(CASE WHEN si.action_type = 'help_image' THEN 1 END) as image_helps,
    
    -- Calcul du niveau d'autonomie (moins d'aide = plus autonome)
    CASE 
        WHEN COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END) = 0 THEN 'Très autonome'
        WHEN COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END) < 
             COUNT(CASE WHEN si.action_type IN ('click', 'double_click') THEN 1 END) * 0.2 THEN 'Autonome'
        WHEN COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END) < 
             COUNT(CASE WHEN si.action_type IN ('click', 'double_click') THEN 1 END) * 0.5 THEN 'Moyennement autonome'
        ELSE 'Besoin d\'accompagnement'
    END as autonomy_level,
    
    -- Mots maîtrisés (lus plusieurs fois)
    COUNT(CASE WHEN si.read_count >= 3 THEN 1 END) as mastered_words,
    
    -- Activité récente
    MAX(si.created_at) as last_activity,
    COUNT(CASE WHEN si.created_at >= NOW() - INTERVAL '7 days' THEN 1 END) as recent_interactions,
    COUNT(CASE WHEN si.created_at >= NOW() - INTERVAL '1 day' THEN 1 END) as today_interactions

FROM students s
LEFT JOIN classrooms c ON s.classroom_id = c.id
LEFT JOIN levels l ON c.level_id = l.id
LEFT JOIN student_interactions si ON s.id = si.student_id
GROUP BY s.id, s.name, c.name, l.name;

-- 2. VUE ANALYTICS TEXTES
-- =====================================================

CREATE OR REPLACE VIEW text_difficulty_analytics AS
SELECT 
    t.id as text_id,
    t.title,
    LENGTH(t.content) as text_length,
    
    -- Statistiques de mots et syllabes
    COUNT(DISTINCT tw.word_id) as total_words,
    COUNT(DISTINCT ws.id) as total_syllables,
    ROUND(COUNT(DISTINCT ws.id)::numeric / NULLIF(COUNT(DISTINCT tw.word_id), 0), 2) as avg_syllables_per_word,
    
    -- Statistiques d'utilisation
    COUNT(DISTINCT si.student_id) as students_attempted,
    COUNT(si.id) as total_interactions,
    
    -- Analyse de difficulté basée sur les demandes d'aide
    COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END) as help_requests,
    ROUND(
        COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END)::numeric / 
        NULLIF(COUNT(si.id), 0) * 100, 2
    ) as help_percentage,
    
    -- Classification de difficulté
    CASE 
        WHEN COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END)::numeric / 
             NULLIF(COUNT(si.id), 0) > 0.4 THEN 'Difficile'
        WHEN COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END)::numeric / 
             NULLIF(COUNT(si.id), 0) > 0.2 THEN 'Moyen'
        ELSE 'Facile'
    END as difficulty_level,
    
    -- Mots les plus problématiques (approximation)
    COUNT(DISTINCT CASE WHEN si.action_type LIKE 'help_%' THEN si.word_id END) as problematic_words_count

FROM texts t
LEFT JOIN text_words tw ON t.id = tw.text_id
LEFT JOIN words w ON tw.word_id = w.id
LEFT JOIN word_syllables ws ON w.id = ws.word_id
LEFT JOIN student_interactions si ON w.id = si.word_id AND t.id = si.text_id
GROUP BY t.id, t.title, t.content;

-- 3. VUE ANALYTICS CLASSES
-- =====================================================

CREATE OR REPLACE VIEW classroom_performance_analytics AS
SELECT 
    c.id as classroom_id,
    c.name as classroom_name,
    l.name as level_name,
    
    -- Statistiques de base
    COUNT(DISTINCT s.id) as total_students,
    COUNT(DISTINCT ct.text_id) as assigned_texts,
    
    -- Performance moyenne de la classe
    ROUND(AVG(spa.total_interactions), 2) as avg_interactions_per_student,
    ROUND(AVG(spa.unique_words_interacted), 2) as avg_words_per_student,
    
    -- Répartition des niveaux d'autonomie
    COUNT(CASE WHEN spa.autonomy_level = 'Très autonome' THEN 1 END) as very_autonomous_students,
    COUNT(CASE WHEN spa.autonomy_level = 'Autonome' THEN 1 END) as autonomous_students,
    COUNT(CASE WHEN spa.autonomy_level = 'Moyennement autonome' THEN 1 END) as moderately_autonomous_students,
    COUNT(CASE WHEN spa.autonomy_level = 'Besoin d\'accompagnement' THEN 1 END) as needs_help_students,
    
    -- Activité de la classe
    COUNT(CASE WHEN spa.recent_interactions > 0 THEN 1 END) as active_students_week,
    COUNT(CASE WHEN spa.today_interactions > 0 THEN 1 END) as active_students_today,
    
    -- Performance globale de la classe (%)
    ROUND(
        (COUNT(CASE WHEN spa.autonomy_level IN ('Très autonome', 'Autonome') THEN 1 END)::numeric / 
         NULLIF(COUNT(DISTINCT s.id), 0)) * 100, 2
    ) as class_autonomy_percentage

FROM classrooms c
LEFT JOIN levels l ON c.level_id = l.id
LEFT JOIN students s ON c.id = s.classroom_id
LEFT JOIN classroom_text ct ON c.id = ct.classroom_id
LEFT JOIN student_progress_analytics spa ON s.id = spa.student_id
GROUP BY c.id, c.name, l.name;

-- 4. VUE ANALYTICS MOTS DIFFICILES
-- =====================================================

CREATE OR REPLACE VIEW difficult_words_analytics AS
SELECT 
    w.id as word_id,
    w.word_text,
    COUNT(DISTINCT ws.id) as syllable_count,
    
    -- Statistiques d'interaction
    COUNT(si.id) as total_interactions,
    COUNT(DISTINCT si.student_id) as students_interacted,
    COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END) as help_requests,
    
    -- Taux de difficulté
    ROUND(
        COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END)::numeric / 
        NULLIF(COUNT(si.id), 0) * 100, 2
    ) as difficulty_percentage,
    
    -- Types d'aide les plus demandés
    COUNT(CASE WHEN si.action_type = 'help_syllables' THEN 1 END) as syllable_help_count,
    COUNT(CASE WHEN si.action_type = 'help_letters' THEN 1 END) as letter_help_count,
    COUNT(CASE WHEN si.action_type = 'help_image' THEN 1 END) as image_help_count,
    
    -- Classification
    CASE 
        WHEN COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END)::numeric / 
             NULLIF(COUNT(si.id), 0) > 0.5 THEN 'Très difficile'
        WHEN COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END)::numeric / 
             NULLIF(COUNT(si.id), 0) > 0.3 THEN 'Difficile'
        WHEN COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END)::numeric / 
             NULLIF(COUNT(si.id), 0) > 0.1 THEN 'Moyen'
        ELSE 'Facile'
    END as difficulty_classification

FROM words w
LEFT JOIN word_syllables ws ON w.id = ws.word_id
LEFT JOIN student_interactions si ON w.id = si.word_id
WHERE EXISTS (SELECT 1 FROM student_interactions WHERE word_id = w.id)
GROUP BY w.id, w.word_text
ORDER BY difficulty_percentage DESC, help_requests DESC;

-- 5. VUE PROGRESSION TEMPORELLE
-- =====================================================

CREATE OR REPLACE VIEW daily_progress_analytics AS
SELECT 
    DATE(si.created_at) as activity_date,
    COUNT(DISTINCT si.student_id) as active_students,
    COUNT(si.id) as total_interactions,
    COUNT(DISTINCT si.word_id) as unique_words_interacted,
    COUNT(DISTINCT si.text_id) as texts_used,
    
    -- Répartition des types d'actions
    COUNT(CASE WHEN si.action_type = 'click' THEN 1 END) as clicks,
    COUNT(CASE WHEN si.action_type = 'double_click' THEN 1 END) as double_clicks,
    COUNT(CASE WHEN si.action_type LIKE 'help_%' THEN 1 END) as help_requests,
    
    -- Taux d'autonomie quotidien
    ROUND(
        (COUNT(CASE WHEN si.action_type IN ('click', 'double_click') THEN 1 END)::numeric / 
         NULLIF(COUNT(si.id), 0)) * 100, 2
    ) as daily_autonomy_rate

FROM student_interactions si
WHERE si.created_at >= NOW() - INTERVAL '30 days'
GROUP BY DATE(si.created_at)
ORDER BY activity_date DESC;

-- 6. FONCTIONS UTILITAIRES POUR LES DASHBOARDS
-- =====================================================

-- Fonction pour obtenir le top des élèves d'une classe
CREATE OR REPLACE FUNCTION get_top_students_by_classroom(classroom_id_param INTEGER, limit_count INTEGER DEFAULT 5)
RETURNS TABLE(
    student_name TEXT,
    total_interactions BIGINT,
    autonomy_level TEXT,
    mastered_words BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        spa.student_name::TEXT,
        spa.total_interactions,
        spa.autonomy_level::TEXT,
        spa.mastered_words
    FROM student_progress_analytics spa
    WHERE spa.classroom_name = (SELECT name FROM classrooms WHERE id = classroom_id_param)
    ORDER BY spa.total_interactions DESC, spa.mastered_words DESC
    LIMIT limit_count;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour obtenir les mots les plus difficiles d'un texte
CREATE OR REPLACE FUNCTION get_difficult_words_by_text(text_id_param INTEGER, limit_count INTEGER DEFAULT 10)
RETURNS TABLE(
    word_text TEXT,
    difficulty_percentage NUMERIC,
    help_requests BIGINT,
    students_struggled BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        dwa.word_text::TEXT,
        dwa.difficulty_percentage,
        dwa.help_requests,
        dwa.students_interacted
    FROM difficult_words_analytics dwa
    JOIN text_words tw ON dwa.word_id = tw.word_id
    WHERE tw.text_id = text_id_param
    ORDER BY dwa.difficulty_percentage DESC, dwa.help_requests DESC
    LIMIT limit_count;
END;
$$ LANGUAGE plpgsql;

-- 7. INDEX POUR OPTIMISER LES VUES
-- =====================================================

-- Index pour améliorer les performances des vues analytics
CREATE INDEX IF NOT EXISTS idx_student_interactions_analytics 
ON student_interactions(student_id, action_type, created_at);

CREATE INDEX IF NOT EXISTS idx_student_interactions_word_text 
ON student_interactions(word_id, text_id, action_type);

-- 8. PROCÉDURE DE RAFRAÎCHISSEMENT DES STATISTIQUES
-- =====================================================

CREATE OR REPLACE FUNCTION refresh_analytics_cache()
RETURNS TEXT AS $$
DECLARE
    result_message TEXT;
BEGIN
    -- Cette fonction peut être appelée périodiquement pour recalculer les statistiques
    -- Pour l'instant, les vues sont calculées à la volée
    
    -- Analyse des performances des vues
    ANALYZE student_interactions;
    ANALYZE words;
    ANALYZE texts;
    ANALYZE students;
    ANALYZE classrooms;
    
    result_message := 'Analytics cache refreshed at ' || NOW();
    
    -- Log de l'opération
    INSERT INTO system_logs (action, details, created_at) 
    VALUES ('analytics_refresh', result_message, NOW());
    
    RETURN result_message;
END;
$$ LANGUAGE plpgsql;

-- Message de confirmation
SELECT 'ReadX Analytics Dashboard views created successfully!' as status;
