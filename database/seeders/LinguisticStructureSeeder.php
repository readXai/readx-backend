<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Text;
use App\Models\Word;
use App\Models\WordUnit;
use App\Models\Root;
use App\Models\Image;
use App\Models\Level;
use App\Models\Classroom;

class LinguisticStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // S'assurer que les racines existent
        $this->call(RootsSeeder::class);
        
        // Créer des niveaux et classes si nécessaire
        $this->createLevelsAndClassrooms();
        
        // Créer des images de test
        $this->createTestImages();
        
        // Créer des textes avec structure linguistique complète
        $this->createStructuredTexts();
        
        $this->command->info('Structure linguistique complète créée avec succès!');
    }
    
    private function createLevelsAndClassrooms(): void
    {
        $levels = [
            ['name' => 'CE1'],
            ['name' => 'CE2'],
            ['name' => 'CM1'],
            ['name' => 'CM2'],
        ];
        
        foreach ($levels as $levelData) {
            $level = Level::firstOrCreate(
                ['name' => $levelData['name']],
                $levelData
            );
            
            // Créer des classes pour chaque niveau
            $sections = ['A', 'B'];
            foreach ($sections as $section) {
                Classroom::firstOrCreate([
                    'level_id' => $level->id,
                    'section' => $section
                ], [
                    'name' => $level->name . $section,
                    'description' => 'Classe ' . $level->name . ' section ' . $section
                ]);
            }
        }
    }
    
    private function createTestImages(): void
    {
        $images = [
            [
                'original_name' => 'كتاب.jpg',
                'filename' => 'book.jpg',
                'description' => 'صورة كتاب للقراءة',
                'image_path' => '/images/book.jpg',
                'thumbnail_path' => '/images/thumbnails/book.jpg',
                'preview_path' => '/images/previews/book.jpg',
                'mobile_path' => '/images/mobile/book.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 150000
            ],
            [
                'original_name' => 'مدرسة.jpg',
                'filename' => 'school.jpg', 
                'description' => 'صورة المدرسة',
                'image_path' => '/images/school.jpg',
                'thumbnail_path' => '/images/thumbnails/school.jpg',
                'preview_path' => '/images/previews/school.jpg',
                'mobile_path' => '/images/mobile/school.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 200000
            ],
            [
                'original_name' => 'طالب.jpg',
                'filename' => 'student.jpg',
                'description' => 'صورة طالب يدرس',
                'image_path' => '/images/student.jpg',
                'thumbnail_path' => '/images/thumbnails/student.jpg',
                'preview_path' => '/images/previews/student.jpg',
                'mobile_path' => '/images/mobile/student.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 180000
            ],
            [
                'original_name' => 'بيت.jpg',
                'filename' => 'house.jpg',
                'description' => 'صورة منزل جميل',
                'image_path' => '/images/house.jpg',
                'thumbnail_path' => '/images/thumbnails/house.jpg',
                'preview_path' => '/images/previews/house.jpg',
                'mobile_path' => '/images/mobile/house.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 220000
            ],
            [
                'original_name' => 'حديقة.jpg',
                'filename' => 'garden.jpg',
                'description' => 'صورة حديقة خضراء',
                'image_path' => '/images/garden.jpg',
                'thumbnail_path' => '/images/thumbnails/garden.jpg',
                'preview_path' => '/images/previews/garden.jpg',
                'mobile_path' => '/images/mobile/garden.jpg',
                'mime_type' => 'image/jpeg',
                'file_size' => 250000
            ]
        ];
        
        foreach ($images as $imageData) {
            Image::firstOrCreate(
                ['original_name' => $imageData['original_name']],
                $imageData
            );
        }
    }
    
    private function createStructuredTexts(): void
    {
        $textsData = [
            [
                'title' => 'يوم في المدرسة',
                'content' => 'ذهب أحمد إلى المدرسة صباحاً. جلس في الفصل وقرأ الكتاب. تعلم أحمد الدرس الجديد. عاد أحمد إلى البيت مساءً.',
                'level' => 'CE1'
            ],
            [
                'title' => 'الحديقة الجميلة',
                'content' => 'في الحديقة أشجار خضراء وأزهار ملونة. يلعب الأطفال تحت الأشجار. تغرد العصافير في الأغصان. الحديقة مكان جميل للراحة.',
                'level' => 'CE2'
            ],
            [
                'title' => 'العائلة السعيدة',
                'content' => 'تتكون العائلة من الأب والأم والأولاد. يحب الأب عائلته كثيراً. تطبخ الأم الطعام اللذيذ. يساعد الأولاد في أعمال البيت. العائلة تعيش في سعادة.',
                'level' => 'CM1'
            ]
        ];
        
        foreach ($textsData as $textData) {
            // Créer le texte sans la colonne level qui n'existe plus
            $text = Text::firstOrCreate(
                ['title' => $textData['title']],
                [
                    'title' => $textData['title'],
                    'content' => $textData['content']
                ]
            );
            
            // Associer le texte à une classe
            $level = Level::where('name', $textData['level'])->first();
            if ($level) {
                $classroom = $level->classrooms()->first();
                if ($classroom) {
                    $text->classrooms()->syncWithoutDetaching([$classroom->id]);
                }
            }
            
            // Créer la structure linguistique pour ce texte
            $this->createLinguisticStructure($text);
        }
    }
    
    private function createLinguisticStructure(Text $text): void
    {
        // Supprimer les anciens mots s'ils existent
        $text->words()->delete();
        
        // Analyser le contenu et créer les mots
        $wordTexts = preg_split('/[\s\p{P}]+/u', $text->content, -1, PREG_SPLIT_NO_EMPTY);
        $wordTexts = array_filter($wordTexts);
        
        $linguisticData = $this->getLinguisticData();
        
        foreach ($wordTexts as $position => $wordText) {
            $word = $text->words()->create([
                'word_text' => $wordText,
                'word_normalized' => Text::normalizeArabicWord($wordText),
                'position' => $position,
                'is_compound' => $this->isCompoundWord($wordText)
            ]);
            
            // Créer les unités pour ce mot
            $this->createWordUnits($word, $wordText, $linguisticData);
        }
        
        // Associer quelques images au texte
        $this->associateImagesToText($text);
    }
    
    private function isCompoundWord(string $word): bool
    {
        // Mots composés simples pour l'exemple
        $compoundWords = ['المدرسة', 'الكتاب', 'الأطفال', 'العصافير', 'الأشجار'];
        return in_array($word, $compoundWords);
    }
    
    private function createWordUnits(Word $word, string $wordText, array $linguisticData): void
    {
        if ($word->is_compound) {
            // Pour les mots composés, créer plusieurs unités
            $this->createCompoundWordUnits($word, $wordText, $linguisticData);
        } else {
            // Pour les mots simples, créer une seule unité
            $this->createSimpleWordUnit($word, $wordText, $linguisticData);
        }
    }
    
    private function createSimpleWordUnit(Word $word, string $wordText, array $linguisticData): void
    {
        $unitData = $linguisticData[$wordText] ?? [
            'type' => $this->guessLinguisticType($wordText),
            'root' => null
        ];
        
        $unit = $word->units()->create([
            'unit_text' => $wordText,
            'unit_normalized' => Text::normalizeArabicWord($wordText),
            'linguistic_type' => $unitData['type'],
            'position' => 0
        ]);
        
        // Associer une racine si disponible
        if ($unitData['root']) {
            $root = Root::where('root_text', $unitData['root'])->first();
            if ($root) {
                $unit->update(['root_id' => $root->id]);
            }
        }
        
        // Associer des images à certaines unités
        $this->associateImagesToUnit($unit);
    }
    
    private function createCompoundWordUnits(Word $word, string $wordText, array $linguisticData): void
    {
        // Exemples de décomposition pour les mots composés
        $decompositions = [
            'المدرسة' => [['ال', 'حرف'], ['مدرسة', 'اسم']],
            'الكتاب' => [['ال', 'حرف'], ['كتاب', 'اسم']],
            'الأطفال' => [['ال', 'حرف'], ['أطفال', 'اسم']],
            'العصافير' => [['ال', 'حرف'], ['عصافير', 'اسم']],
            'الأشجار' => [['ال', 'حرف'], ['أشجار', 'اسم']]
        ];
        
        $units = $decompositions[$wordText] ?? [[$wordText, 'اسم']];
        
        foreach ($units as $position => $unitInfo) {
            [$unitText, $type] = $unitInfo;
            
            $unit = $word->units()->create([
                'unit_text' => $unitText,
                'unit_normalized' => Text::normalizeArabicWord($unitText),
                'linguistic_type' => $type,
                'position' => $position
            ]);
            
            // Associer une racine pour les noms
            if ($type === 'اسم') {
                $this->associateRootToUnit($unit, $unitText);
            }
            
            // Associer des images aux unités principales
            if ($position > 0) { // Pas pour l'article "ال"
                $this->associateImagesToUnit($unit);
            }
        }
    }
    
    private function associateRootToUnit(WordUnit $unit, string $unitText): void
    {
        $rootMappings = [
            'مدرسة' => 'مدر',
            'كتاب' => 'كتب',
            'أطفال' => 'طلب', // من طفل
            'عصافير' => 'طير',
            'أشجار' => 'شجر'
        ];
        
        $rootText = $rootMappings[$unitText] ?? null;
        if ($rootText) {
            $root = Root::where('root_text', $rootText)->first();
            if ($root) {
                $unit->update(['root_id' => $root->id]);
            }
        }
    }
    
    private function guessLinguisticType(string $word): string
    {
        // Règles simples pour deviner le type linguistique
        $verbs = ['ذهب', 'جلس', 'قرأ', 'تعلم', 'عاد', 'يلعب', 'تغرد', 'يحب', 'تطبخ', 'يساعد', 'تعيش'];
        $prepositions = ['في', 'إلى', 'من', 'على', 'تحت', 'مع'];
        
        if (in_array($word, $verbs)) {
            return 'فعل';
        } elseif (in_array($word, $prepositions)) {
            return 'حرف';
        } else {
            return 'اسم';
        }
    }
    
    private function getLinguisticData(): array
    {
        return [
            'أحمد' => ['type' => 'اسم', 'root' => null],
            'ذهب' => ['type' => 'فعل', 'root' => 'ذهب'],
            'إلى' => ['type' => 'حرف', 'root' => null],
            'صباحاً' => ['type' => 'اسم', 'root' => null],
            'جلس' => ['type' => 'فعل', 'root' => 'جلس'],
            'في' => ['type' => 'حرف', 'root' => null],
            'الفصل' => ['type' => 'اسم', 'root' => null],
            'قرأ' => ['type' => 'فعل', 'root' => 'قرأ'],
            'تعلم' => ['type' => 'فعل', 'root' => 'علم'],
            'الدرس' => ['type' => 'اسم', 'root' => 'درس'],
            'الجديد' => ['type' => 'اسم', 'root' => null],
            'عاد' => ['type' => 'فعل', 'root' => null],
            'البيت' => ['type' => 'اسم', 'root' => 'بيت'],
            'مساءً' => ['type' => 'اسم', 'root' => null],
        ];
    }
    
    private function associateImagesToText(Text $text): void
    {
        $imageKeywords = [
            'المدرسة' => ['مدرسة.jpg', 'طالب.jpg'],
            'الحديقة' => ['حديقة.jpg'],
            'البيت' => ['بيت.jpg'],
            'الكتاب' => ['كتاب.jpg']
        ];
        
        foreach ($imageKeywords as $keyword => $imageNames) {
            if (str_contains($text->content, $keyword)) {
                foreach ($imageNames as $imageName) {
                    $image = Image::where('original_name', $imageName)->first();
                    if ($image) {
                        $text->images()->syncWithoutDetaching([$image->id]);
                    }
                }
            }
        }
    }
    
    private function associateImagesToUnit(WordUnit $unit): void
    {
        $unitImageMappings = [
            'مدرسة' => 'مدرسة.jpg',
            'كتاب' => 'كتاب.jpg',
            'بيت' => 'بيت.jpg',
            'حديقة' => 'حديقة.jpg'
        ];
        
        $imageName = $unitImageMappings[$unit->unit_text] ?? null;
        if ($imageName) {
            $image = Image::where('original_name', $imageName)->first();
            if ($image) {
                $unit->images()->syncWithoutDetaching([$image->id]);
            }
        }
    }
}
