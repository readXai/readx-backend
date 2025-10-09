-- =====================================================
-- ReadX Arabic Learning - PostgreSQL Optimizations
-- Optimisations 100% gratuites pour l'apprentissage arabe
-- =====================================================

-- 1. INDEX AVANCÉS POUR LA RECHERCHE ARABE
-- =====================================================

-- Index GIN pour recherche full-text en arabe sur les textes
CREATE INDEX IF NOT EXISTS idx_texts_content_arabic_gin 
ON texts USING gin(to_tsvector('arabic', content));

-- Index trigram pour recherche floue sur les mots arabes
CREATE INDEX IF NOT EXISTS idx_words_text_trgm 
ON words USING gin(word_text gin_trgm_ops);

-- Index composé pour les syllabes (performance queries fréquentes)
CREATE INDEX IF NOT EXISTS idx_syllables_composite 
ON word_syllables(word_id, syllable_position, syllable_type);

-- Index pour les interactions d'élèves (analytics)
CREATE INDEX IF NOT EXISTS idx_interactions_student_time 
ON student_interactions(student_id, created_at DESC);

-- Index pour les sessions de lecture (performance)
CREATE INDEX IF NOT EXISTS idx_sessions_student_active 
ON reading_sessions(student_id, is_active, created_at DESC);

-- Index pour les associations texte-classe (filtrage rapide)
CREATE INDEX IF NOT EXISTS idx_classroom_text_lookup 
ON classroom_text(classroom_id, text_id);

-- 2. VUES MATÉRIALISÉES POUR ANALYTICS
-- =====================================================

-- Vue pour les statistiques d'élèves en temps réel
CREATE MATERIALIZED VIEW IF NOT EXISTS student_analytics AS
SELECT 
    s.id as student_id,
    s.name as student_name,
    c.name as classroom_name,
    l.name as level_name,
    COUNT(DISTINCT si.id) as total_interactions,
    COUNT(DISTINCT si.word_id) as unique_words_read,
    AVG(si.reading_time) as avg_reading_time,
    SUM(si.reading_time) as total_reading_time,
    COUNT(DISTINCT si.text_id) as texts_attempted,
    MAX(si.created_at) as last_activity,
    -- Calcul du niveau de maîtrise (mots lus plusieurs fois)
    COUNT(CASE WHEN si.interaction_count >= 3 THEN 1 END) as mastered_words,
    -- Progression récente (7 derniers jours)
    COUNT(CASE WHEN si.created_at >= NOW() - INTERVAL '7 days' THEN 1 END) as recent_interactions
FROM students s
LEFT JOIN classrooms c ON s.classroom_id = c.id
LEFT JOIN levels l ON c.level_id = l.id
LEFT JOIN student_interactions si ON s.id = si.student_id
GROUP BY s.id, s.name, c.name, l.name;

-- Index sur la vue matérialisée
CREATE UNIQUE INDEX IF NOT EXISTS idx_student_analytics_pk 
ON student_analytics(student_id);

-- Vue pour les statistiques de textes
CREATE MATERIALIZED VIEW IF NOT EXISTS text_analytics AS
SELECT 
    t.id as text_id,
    t.title,
    t.content,
    COUNT(DISTINCT w.id) as total_words,
    COUNT(DISTINCT ws.id) as total_syllables,
    COUNT(DISTINCT si.student_id) as students_attempted,
    AVG(si.reading_time) as avg_reading_time,
    COUNT(si.id) as total_interactions,
    -- Niveau de difficulté basé sur les interactions
    CASE 
        WHEN AVG(si.reading_time) > 300 THEN 'Difficile'
        WHEN AVG(si.reading_time) > 180 THEN 'Moyen'
        ELSE 'Facile'
    END as difficulty_level,
    -- Mots les plus difficiles
    array_agg(DISTINCT w.word_text ORDER BY AVG(si.reading_time) DESC LIMIT 5) as challenging_words
FROM texts t
LEFT JOIN text_words tw ON t.id = tw.text_id
LEFT JOIN words w ON tw.word_id = w.id
LEFT JOIN word_syllables ws ON w.id = ws.word_id
LEFT JOIN student_interactions si ON w.id = si.word_id AND t.id = si.text_id
GROUP BY t.id, t.title, t.content;

-- Vue pour les statistiques de classes
CREATE MATERIALIZED VIEW IF NOT EXISTS classroom_analytics AS
SELECT 
    c.id as classroom_id,
    c.name as classroom_name,
    l.name as level_name,
    COUNT(DISTINCT s.id) as total_students,
    COUNT(DISTINCT ct.text_id) as assigned_texts,
    AVG(sa.total_interactions) as avg_interactions_per_student,
    AVG(sa.avg_reading_time) as avg_reading_time,
    SUM(sa.total_reading_time) as total_class_time,
    -- Élèves les plus actifs
    array_agg(s.name ORDER BY sa.total_interactions DESC LIMIT 3) as top_students,
    -- Progression de classe (%)
    ROUND(AVG(sa.mastered_words::float / NULLIF(sa.unique_words_read, 0)) * 100, 2) as mastery_percentage
FROM classrooms c
LEFT JOIN levels l ON c.level_id = l.id
LEFT JOIN students s ON c.id = s.classroom_id
LEFT JOIN classroom_text ct ON c.id = ct.classroom_id
LEFT JOIN student_analytics sa ON s.id = sa.student_id
GROUP BY c.id, c.name, l.name;

-- 3. FONCTIONS PERSONNALISÉES POUR L'ARABE
-- =====================================================

-- Fonction pour nettoyer le texte arabe (supprimer diacritiques)
CREATE OR REPLACE FUNCTION clean_arabic_text(input_text TEXT)
RETURNS TEXT AS $$
BEGIN
    -- Supprime les diacritiques arabes communs
    RETURN regexp_replace(input_text, '[َُِّْٰٱٲٳٵٶٷٸٹٺٻټٽپٿڀځڂڃڄڅچڇڈډڊڋڌڍڎڏڐڑڒړڔڕږڗژڙښڛڜڝڞڟڠڡڢڣڤڥڦڧڨکڪګڬڭڮگڰڱڲڳڴڵڶڷڸڹںڻڼڽھڿۀہۂۃۄۅۆۇۈۉۊۋیۍێۏېۑےۓ۔ۖۗۘۙۚۛۜ۝۞ۣ۟۠ۡۢۤۥۦۧۨ۩۪ۭ۫۬ۮۯ۰۱۲۳۴۵۶۷۸۹ۺۻۼ۽۾ۿ]', '', 'g');
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- Fonction pour calculer la similarité phonétique arabe
CREATE OR REPLACE FUNCTION arabic_similarity(text1 TEXT, text2 TEXT)
RETURNS FLOAT AS $$
BEGIN
    -- Utilise pg_trgm après nettoyage des diacritiques
    RETURN similarity(clean_arabic_text(text1), clean_arabic_text(text2));
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- Fonction pour recherche intelligente de mots arabes
CREATE OR REPLACE FUNCTION search_arabic_words(query_text TEXT, limit_count INTEGER DEFAULT 10)
RETURNS TABLE(
    word_id INTEGER,
    word_text TEXT,
    similarity_score FLOAT,
    syllable_count BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        w.id,
        w.word_text,
        arabic_similarity(w.word_text, query_text) as sim_score,
        COUNT(ws.id) as syllables
    FROM words w
    LEFT JOIN word_syllables ws ON w.id = ws.word_id
    WHERE arabic_similarity(w.word_text, query_text) > 0.3
    GROUP BY w.id, w.word_text
    ORDER BY sim_score DESC, syllables ASC
    LIMIT limit_count;
END;
$$ LANGUAGE plpgsql;

-- 4. TRIGGERS POUR MISE À JOUR AUTOMATIQUE
-- =====================================================

-- Fonction pour rafraîchir les vues matérialisées
CREATE OR REPLACE FUNCTION refresh_analytics_views()
RETURNS TRIGGER AS $$
BEGIN
    -- Rafraîchit les vues de manière asynchrone
    PERFORM pg_notify('refresh_analytics', 'student_interactions_updated');
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

-- Trigger sur les interactions d'élèves
DROP TRIGGER IF EXISTS trigger_refresh_analytics ON student_interactions;
CREATE TRIGGER trigger_refresh_analytics
    AFTER INSERT OR UPDATE OR DELETE ON student_interactions
    FOR EACH ROW EXECUTE FUNCTION refresh_analytics_views();

-- 5. CONFIGURATION OPTIMALE POUR L'ARABE
-- =====================================================

-- Configuration de recherche full-text pour l'arabe
ALTER TEXT SEARCH CONFIGURATION arabic SET default;

-- Optimisation des paramètres PostgreSQL pour ReadX
-- (Ces paramètres peuvent être ajustés dans postgresql.conf)
/*
shared_buffers = 256MB                    # Cache mémoire
effective_cache_size = 1GB               # Cache OS estimé
random_page_cost = 1.1                   # SSD optimization
seq_page_cost = 1.0                      # Sequential scan cost
work_mem = 16MB                          # Mémoire par opération
maintenance_work_mem = 64MB              # Maintenance operations
checkpoint_completion_target = 0.9       # Checkpoint tuning
wal_buffers = 16MB                       # WAL buffer size
*/

-- 6. PROCÉDURES DE MAINTENANCE
-- =====================================================

-- Procédure pour rafraîchir toutes les vues matérialisées
CREATE OR REPLACE FUNCTION refresh_all_analytics()
RETURNS VOID AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY student_analytics;
    REFRESH MATERIALIZED VIEW CONCURRENTLY text_analytics;
    REFRESH MATERIALIZED VIEW CONCURRENTLY classroom_analytics;
    
    -- Log de la mise à jour
    INSERT INTO system_logs (action, details, created_at) 
    VALUES ('analytics_refresh', 'All materialized views refreshed', NOW())
    ON CONFLICT DO NOTHING;
END;
$$ LANGUAGE plpgsql;

-- Procédure pour analyser les performances
CREATE OR REPLACE FUNCTION analyze_performance()
RETURNS TABLE(
    table_name TEXT,
    total_size TEXT,
    index_size TEXT,
    row_count BIGINT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        schemaname||'.'||tablename as table_name,
        pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as total_size,
        pg_size_pretty(pg_indexes_size(schemaname||'.'||tablename)) as index_size,
        n_tup_ins + n_tup_upd + n_tup_del as row_count
    FROM pg_stat_user_tables
    ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
END;
$$ LANGUAGE plpgsql;

-- 7. CRÉATION TABLE DE LOGS SYSTÈME
-- =====================================================

CREATE TABLE IF NOT EXISTS system_logs (
    id SERIAL PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_system_logs_action_time 
ON system_logs(action, created_at DESC);

-- Message de confirmation
SELECT 'ReadX PostgreSQL optimizations applied successfully!' as status;
