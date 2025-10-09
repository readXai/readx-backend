<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Root;

class RootsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roots = [
            // Racines communes pour les noms
            ['root_text' => 'كتب', 'root_normalized' => 'كتب', 'description' => 'Racine liée à l\'écriture et aux livres'],
            ['root_text' => 'قرأ', 'root_normalized' => 'قرا', 'description' => 'Racine liée à la lecture'],
            ['root_text' => 'علم', 'root_normalized' => 'علم', 'description' => 'Racine liée au savoir et à la science'],
            ['root_text' => 'درس', 'root_normalized' => 'درس', 'description' => 'Racine liée à l\'étude et à l\'enseignement'],
            ['root_text' => 'بيت', 'root_normalized' => 'بيت', 'description' => 'Racine liée à la maison et au foyer'],
            ['root_text' => 'مدر', 'root_normalized' => 'مدر', 'description' => 'Racine liée à l\'école et à l\'enseignement'],
            ['root_text' => 'طلب', 'root_normalized' => 'طلب', 'description' => 'Racine liée à la demande et à la recherche'],
            ['root_text' => 'عمل', 'root_normalized' => 'عمل', 'description' => 'Racine liée au travail et à l\'action'],
            
            // Racines communes pour les verbes
            ['root_text' => 'ذهب', 'root_normalized' => 'ذهب', 'description' => 'Racine du verbe aller'],
            ['root_text' => 'جلس', 'root_normalized' => 'جلس', 'description' => 'Racine du verbe s\'asseoir'],
            ['root_text' => 'أكل', 'root_normalized' => 'اكل', 'description' => 'Racine du verbe manger'],
            ['root_text' => 'شرب', 'root_normalized' => 'شرب', 'description' => 'Racine du verbe boire'],
            ['root_text' => 'نوم', 'root_normalized' => 'نوم', 'description' => 'Racine du verbe dormir'],
            ['root_text' => 'لعب', 'root_normalized' => 'لعب', 'description' => 'Racine du verbe jouer'],
            ['root_text' => 'كلم', 'root_normalized' => 'كلم', 'description' => 'Racine du verbe parler'],
            ['root_text' => 'سمع', 'root_normalized' => 'سمع', 'description' => 'Racine du verbe entendre'],
            
            // Racines pour les couleurs
            ['root_text' => 'حمر', 'root_normalized' => 'حمر', 'description' => 'Racine de la couleur rouge'],
            ['root_text' => 'زرق', 'root_normalized' => 'زرق', 'description' => 'Racine de la couleur bleue'],
            ['root_text' => 'خضر', 'root_normalized' => 'خضر', 'description' => 'Racine de la couleur verte'],
            ['root_text' => 'صفر', 'root_normalized' => 'صفر', 'description' => 'Racine de la couleur jaune'],
            
            // Racines pour la famille
            ['root_text' => 'أبو', 'root_normalized' => 'ابو', 'description' => 'Racine du père'],
            ['root_text' => 'أمم', 'root_normalized' => 'امم', 'description' => 'Racine de la mère'],
            ['root_text' => 'ولد', 'root_normalized' => 'ولد', 'description' => 'Racine de l\'enfant/fils'],
            ['root_text' => 'بنت', 'root_normalized' => 'بنت', 'description' => 'Racine de la fille'],
            
            // Racines pour les animaux
            ['root_text' => 'قطط', 'root_normalized' => 'قطط', 'description' => 'Racine du chat'],
            ['root_text' => 'كلب', 'root_normalized' => 'كلب', 'description' => 'Racine du chien'],
            ['root_text' => 'حصن', 'root_normalized' => 'حصن', 'description' => 'Racine du cheval'],
            ['root_text' => 'طير', 'root_normalized' => 'طير', 'description' => 'Racine de l\'oiseau'],
        ];

        foreach ($roots as $rootData) {
            Root::firstOrCreate(
                ['root_text' => $rootData['root_text']],
                $rootData
            );
        }

        $this->command->info('Racines arabes créées avec succès!');
    }
}
