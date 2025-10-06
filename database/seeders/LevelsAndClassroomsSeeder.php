<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Seeder;

class LevelsAndClassroomsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les niveaux scolaires
        $levels = [
            ['name' => 'CE1', 'description' => 'Cours Élémentaire 1ère année', 'order' => 1],
            ['name' => 'CE2', 'description' => 'Cours Élémentaire 2ème année', 'order' => 2],
            ['name' => 'CM1', 'description' => 'Cours Moyen 1ère année', 'order' => 3],
            ['name' => 'CM2', 'description' => 'Cours Moyen 2ème année', 'order' => 4],
        ];

        foreach ($levels as $levelData) {
            Level::firstOrCreate(
                ['name' => $levelData['name']],
                $levelData
            );
        }

        // Créer les classes pour chaque niveau (3 classes par niveau)
        $levelsCreated = Level::all();
        
        foreach ($levelsCreated as $level) {
            // Créer 3 classes par niveau (A, B, C)
            $sections = ['A', 'B', 'C'];
            
            foreach ($sections as $section) {
                $classroom = Classroom::firstOrCreate([
                    'level_id' => $level->id,
                    'section' => $section
                ], [
                    'name' => $level->name . $section,
                    'description' => "Classe de {$level->name} section {$section}"
                ]);
                
                // Créer 25-30 élèves par classe avec des prénoms variés
                $this->createStudentsForClassroom($classroom, $level->name);
            }
        }

        $this->command->info('Niveaux, classes et élèves créés avec succès!');
    }
    
    /**
     * Créer des élèves pour une classe donnée
     */
    private function createStudentsForClassroom(Classroom $classroom, string $levelName): void
    {
        // Prénoms français et arabes mélangés pour simuler une classe diversifiée
        $firstNames = [
            // Prénoms garçons
            'Ahmed', 'Omar', 'Youssef', 'Ali', 'Mohamed', 'Hamza', 'Amine', 'Karim', 'Samir', 'Nabil',
            'Lucas', 'Hugo', 'Léo', 'Louis', 'Arthur', 'Gabriel', 'Raphaël', 'Adam', 'Jules', 'Maël',
            'Zakaria', 'Mehdi', 'Rayan', 'Ilyas', 'Ayoub', 'Ismail', 'Bilal', 'Othmane', 'Sami', 'Walid',
            // Prénoms filles
            'Fatima', 'Aicha', 'Khadija', 'Maryam', 'Salma', 'Nour', 'Lina', 'Amina', 'Yasmine', 'Zineb',
            'Emma', 'Louise', 'Chloé', 'Camille', 'Manon', 'Sarah', 'Lola', 'Inès', 'Jade', 'Léa',
            'Malak', 'Rim', 'Dounia', 'Aya', 'Imane', 'Hajar', 'Manal', 'Siham', 'Widad', 'Ghita'
        ];
        
        $lastNames = [
            'Ben Ali', 'Alami', 'Benali', 'Mansouri', 'Tazi', 'Bennani', 'Fassi', 'Idrissi', 'Lahlou', 'Berrada',
            'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon',
            'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Fournier', 'Morel',
            'El Amrani', 'Hajji', 'Chakir', 'Ouali', 'Ziani', 'Kadiri', 'Sabri', 'Naciri', 'Filali', 'Senhaji'
        ];
        
        // Nombre d'élèves entre 25 et 30
        $studentCount = rand(25, 30);
        $usedNames = [];
        
        for ($i = 0; $i < $studentCount; $i++) {
            // Éviter les doublons de noms dans la même classe
            do {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $fullName = $firstName . ' ' . $lastName;
            } while (in_array($fullName, $usedNames));
            
            $usedNames[] = $fullName;
            
            Student::create([
                'name' => $fullName,
                'classroom_id' => $classroom->id,
                'age' => $this->getAgeForLevel($levelName)
            ]);
        }
    }

    /**
     * Obtenir un âge approprié selon le niveau scolaire
     */
    private function getAgeForLevel(string $levelName): int
    {
        // Âges selon le niveau
        $ageRanges = [
            'CE1' => [6, 7],
            'CE2' => [7, 8],
            'CM1' => [8, 9],
            'CM2' => [9, 10]
        ];

        $ageRange = $ageRanges[$levelName] ?? [7, 8];
        return rand($ageRange[0], $ageRange[1]);
    }
}
