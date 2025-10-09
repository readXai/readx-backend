<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Text;
use App\Models\Word;

class WordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "Extraction des mots des textes...\n";
        
        $texts = Text::all();
        
        foreach ($texts as $text) {
            echo "Traitement du texte: {$text->title}\n";
            
            // Nettoyer et diviser le contenu en mots
            $content = $text->content;
            
            // Supprimer la ponctuation et diviser par espaces
            $cleanContent = preg_replace('/[^\p{Arabic}\s]/u', ' ', $content);
            $words = preg_split('/\s+/', trim($cleanContent), -1, PREG_SPLIT_NO_EMPTY);
            
            $position = 1;
            
            foreach ($words as $wordText) {
                if (empty(trim($wordText))) {
                    continue;
                }
                
                // Créer ou récupérer le mot
                $normalizedWord = $this->normalizeWord(trim($wordText));
                $word = Word::firstOrCreate([
                    'word_normalized' => $normalizedWord
                ], [
                    'word_text' => trim($wordText),
                    'position' => 0,
                    'is_compound' => false
                ]);
                
                // Associer le mot au texte (relation directe maintenant)
                if (!$word->text_id) {
                    $word->update([
                        'text_id' => $text->id,
                        'position' => $position
                    ]);
                }
                
                $position++;
            }
            
            echo "  Mots extraits: " . ($position - 1) . "\n";
        }
        
        echo "Extraction des mots terminee!\n";
    }
    
    /**
     * Normaliser un mot arabe (supprimer les harakat et l'article défini)
     */
    private function normalizeWord(string $word): string
    {
        // Supprimer les harakat (diacritiques arabes)
        $normalized = preg_replace('/[\x{064B}-\x{0652}]/u', '', $word);
        
        // Supprimer l'article défini ال au début
        $normalized = preg_replace('/^ال/u', '', $normalized);
        
        return trim($normalized);
    }
}
