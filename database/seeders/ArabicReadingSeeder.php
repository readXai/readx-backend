<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Text;
use App\Models\Word;
use App\Models\Image;
use App\Models\StudentInteraction;
use App\Models\ReadingSession;
use Illuminate\Support\Facades\DB;

class ArabicReadingSeeder extends Seeder
{
    /**
     * Créer des données d'exemple pour l'application de lecture arabe
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // Créer des élèves d'exemple
            $students = [
                ['name' => 'Ahmed', 'level' => 'CE1', 'age' => 7],
                ['name' => 'Fatima', 'level' => 'CE2', 'age' => 8],
                ['name' => 'Omar', 'level' => 'CM1', 'age' => 9],
                ['name' => 'Aisha', 'level' => 'CM2', 'age' => 10],
                ['name' => 'Youssef', 'level' => 'CE1', 'age' => 7],
            ];
            
            foreach ($students as $studentData) {
                Student::create($studentData);
            }
            
            // Créer des textes d'exemple avec contenu arabe
            $texts = [
                [
                    'title' => 'المدرسة',
                    'content' => 'المدرسة مكان جميل. يذهب الأطفال إلى المدرسة كل يوم. المعلم يعلم الطلاب.',
                    'difficulty_level' => 'CE1'
                ],
                [
                    'title' => 'البيت',
                    'content' => 'البيت هو المكان الذي نعيش فيه. في البيت نجد الأم والأب والأطفال.',
                    'difficulty_level' => 'CE2'
                ],
                [
                    'title' => 'الحديقة',
                    'content' => 'الحديقة مليئة بالأشجار والورود الجميلة. الأطفال يلعبون في الحديقة.',
                    'difficulty_level' => 'CM1'
                ]
            ];
            
            foreach ($texts as $textData) {
                $text = Text::create($textData);
                $this->processTextWords($text);
            }
            
            // Créer quelques images d'exemple (chemins fictifs)
            $images = [
                ['image_path' => 'images/school.jpg', 'description' => 'Image d\'une école'],
                ['image_path' => 'images/teacher.jpg', 'description' => 'Image d\'un enseignant'],
                ['image_path' => 'images/house.jpg', 'description' => 'Image d\'une maison'],
                ['image_path' => 'images/garden.jpg', 'description' => 'Image d\'un jardin'],
                ['image_path' => 'images/children.jpg', 'description' => 'Image d\'enfants'],
            ];
            
            foreach ($images as $imageData) {
                Image::create($imageData);
            }
            
            // Associer quelques images aux mots
            $this->associateImagesWithWords();
            
            // Créer quelques interactions d'exemple
            $this->createSampleInteractions();
            
            // Créer quelques sessions de lecture d'exemple
            $this->createSampleReadingSessions();
            
            DB::commit();
            
            $this->command->info('Données d\'exemple créées avec succès!');
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('Erreur lors de la création des données: ' . $e->getMessage());
        }
    }
    
    /**
     * Analyser le texte et créer/associer les mots
     */
    private function processTextWords(Text $text): void
    {
        $words = $text->parseWordsFromContent();
        
        foreach ($words as $position => $wordText) {
            $normalized = Text::normalizeArabicWord($wordText);
            
            // Chercher ou créer le mot
            $word = Word::firstOrCreate(
                ['word' => $wordText],
                ['word_normalized' => $normalized]
            );
            
            // Associer le mot au texte avec sa position
            $text->words()->attach($word->id, ['position' => $position + 1]);
        }
    }
    
    /**
     * Associer des images aux mots
     */
    private function associateImagesWithWords(): void
    {
        $associations = [
            'المدرسة' => 1, // school image
            'المعلم' => 2,  // teacher image
            'البيت' => 3,   // house image
            'الحديقة' => 4, // garden image
            'الأطفال' => 5  // children image
        ];
        
        foreach ($associations as $wordText => $imageId) {
            $word = Word::where('word', $wordText)->first();
            $image = Image::find($imageId);
            
            if ($word && $image) {
                $word->images()->attach($image->id);
            }
        }
    }
    
    /**
     * Créer des interactions d'exemple
     */
    private function createSampleInteractions(): void
    {
        $students = Student::all();
        $texts = Text::all();
        $words = Word::all();
        
        foreach ($students->take(3) as $student) {
            foreach ($texts->take(2) as $text) {
                foreach ($words->take(5) as $word) {
                    StudentInteraction::create([
                        'student_id' => $student->id,
                        'text_id' => $text->id,
                        'word_id' => $word->id,
                        'action_type' => fake()->randomElement(['click', 'double_click', 'help_syllables']),
                        'read_count' => fake()->numberBetween(1, 5)
                    ]);
                }
            }
        }
    }
    
    /**
     * Créer des sessions de lecture d'exemple
     */
    private function createSampleReadingSessions(): void
    {
        $students = Student::all();
        $texts = Text::all();
        
        foreach ($students->take(3) as $student) {
            foreach ($texts->take(2) as $text) {
                ReadingSession::create([
                    'student_id' => $student->id,
                    'text_id' => $text->id,
                    'start_time' => now()->subMinutes(fake()->numberBetween(10, 60)),
                    'end_time' => now()->subMinutes(fake()->numberBetween(1, 10)),
                    'duration' => fake()->numberBetween(300, 1800), // 5-30 minutes
                    'words_read' => fake()->numberBetween(10, 50),
                    'help_requested' => fake()->numberBetween(0, 10)
                ]);
            }
        }
    }
}
