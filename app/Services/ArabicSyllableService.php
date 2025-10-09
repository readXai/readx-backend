<?php

namespace App\Services;

class ArabicSyllableService
{
    // Voyelles arabes courtes
    protected $shortVowels = ['َ', 'ُ', 'ِ'];
    protected $sukun = 'ْ';
    protected $shadda = 'ّ';

    /**
     * Découpe un mot arabe en syllabes.
     */
    public function splitIntoSyllables(string $word): array
    {
        $letters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
        $normalized = $this->normalizeWord($letters);
        return $this->segmentSyllables($normalized);
    }

    /**
     * Normalise le mot :
     * - double les lettres avec chadda
     * - ajoute fatha implicite si pas de voyelle
     */
    protected function normalizeWord(array $letters): array
    {
        $normalized = [];

        for ($i = 0; $i < count($letters); $i++) {
            $char = $letters[$i];

            // Si c'est une chadda
            if ($char === $this->shadda && $i > 0) {
                $previous = end($normalized);
                array_pop($normalized);
                // Dupliquer la lettre précédente avec soukoun sur la première
                $normalized[] = $previous . $this->sukun;
                $normalized[] = $previous;
                continue;
            }

            // Si c'est une lettre, vérifier si elle est suivie d'une voyelle
            if ($this->isArabicLetter($char)) {
                $next = $letters[$i + 1] ?? null;
                if (!$next || (!$this->isShortVowel($next) && $next !== $this->sukun && $next !== $this->shadda)) {
                    // Si pas de voyelle → ajouter fatha implicite
                    $normalized[] = $char;
                    $normalized[] = 'َ';
                    continue;
                }
            }

            $normalized[] = $char;
        }

        return $normalized;
    }

    /**
     * Découper en syllabes selon structure C(V)(C)
     */
    protected function segmentSyllables(array $letters): array
    {
        $syllables = [];
        $current = '';

        foreach ($letters as $char) {
            $current .= $char;

            // Si c'est une voyelle, on peut terminer une syllabe
            if ($this->isShortVowel($char)) {
                if (trim($current) !== '') {
                    $syllables[] = $current;
                }
                $current = '';
            }

            // Si c'est un soukoun, on ferme aussi une syllabe
            if ($char === $this->sukun) {
                if (trim($current) !== '') {
                    $syllables[] = $current;
                }
                $current = '';
            }
        }

        // Ajouter le reste s'il reste des lettres
        if (trim($current) !== '') {
            $syllables[] = $current;
        }

        // Filtrer les syllabes vides
        return array_filter($syllables, function($syllable) {
            return trim($syllable) !== '';
        });
    }

    protected function isArabicLetter(string $char): bool
    {
        return preg_match('/\p{Arabic}/u', $char);
    }

    protected function isShortVowel(string $char): bool
    {
        return in_array($char, $this->shortVowels);
    }

    /**
     * Générer les syllabes d'un mot arabe selon les règles linguistiques officielles
     * (Méthode de compatibilité avec l'API existante)
     */
    public function generateSyllables(string $wordText): array
    {
        $syllables = $this->splitIntoSyllables($wordText);
        
        // Convertir au format attendu par l'API
        $result = [];
        foreach ($syllables as $index => $syllable) {
            $result[] = [
                'syllable_text' => $syllable,
                'syllable_type' => $this->determineSyllableType($syllable),
                'position' => $index,
                'is_inferred' => false,
                'suggestion' => null
            ];
        }
        
        return $result;
    }

    /**
     * Détermine le type de syllabe (CV, CVC, etc.)
     * Compatible avec les contraintes PostgreSQL : CV, CVC, CVCC, CVV, CVVC, V, VC
     */
    protected function determineSyllableType(string $syllable): string
    {
        $chars = preg_split('//u', $syllable, -1, PREG_SPLIT_NO_EMPTY);
        $pattern = '';
        
        foreach ($chars as $char) {
            if ($this->isArabicLetter($char) && !$this->isShortVowel($char) && $char !== $this->sukun) {
                $pattern .= 'C'; // Consonne
            } elseif ($this->isShortVowel($char)) {
                $pattern .= 'V'; // Voyelle
            }
            // Ignorer les autres caractères (soukoun, etc.) pour le pattern
        }
        
        // Normaliser les patterns non-standard vers des types acceptés par la DB
        $allowedTypes = ['CV', 'CVC', 'CVCC', 'CVV', 'CVVC', 'V', 'VC'];
        
        // Si le pattern généré n'est pas dans la liste, le normaliser
        if (!in_array($pattern, $allowedTypes)) {
            // Règles de normalisation pour les cas spéciaux
            if (preg_match('/^C+V/', $pattern)) {
                // CCV, CCCV, etc. → CV (consonne(s) + voyelle)
                return 'CV';
            } elseif (preg_match('/^C+$/', $pattern)) {
                // CC, CCC, etc. → CVC (ajouter voyelle implicite)
                return 'CVC';
            } elseif (preg_match('/^V+/', $pattern)) {
                // VV, VVV, etc. → V (voyelle longue)
                return 'V';
            } else {
                // Cas par défaut
                return 'CV';
            }
        }
        
        return $pattern ?: 'CV';
    }
}
