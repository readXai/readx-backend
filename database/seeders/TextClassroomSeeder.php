<?php

namespace Database\Seeders;

use App\Models\Text;
use App\Models\Classroom;
use App\Models\Level;
use Illuminate\Database\Seeder;

class TextClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des textes d'exemple et les associer aux classes
        $levels = Level::all();
        $classrooms = Classroom::all();

        // Textes pour CE1 (niveau débutant)
        $ce1Classrooms = $classrooms->filter(fn($c) => $c->level->name === 'CE1');
        $ce1Texts = [
            [
                'title' => 'القطة الصغيرة',
                'content' => 'هذه قِطَّةٌ صَغيرَةٌ. القِطَّةُ تَلعَبُ في الحَديقَةِ. القِطَّةُ تُحِبُّ اللَّبَنَ.',
                'difficulty_level' => 'CE1'
            ],
            [
                'title' => 'البيت الجميل',
                'content' => 'هذا بَيتٌ جَميلٌ. البَيتُ كَبيرٌ. في البَيتِ حَديقَةٌ صَغيرَةٌ.',
                'difficulty_level' => 'CE1'
            ],
            [
                'title' => 'الكلب الوفي',
                'content' => 'عِندي كَلبٌ صَغيرٌ. الكَلبُ وَفِيٌّ. يَلعَبُ مَعي كُلَّ يَومٍ.',
                'difficulty_level' => 'CE1'
            ],
            [
                'title' => 'في المدرسة',
                'content' => 'أَذهَبُ إِلى المَدرَسَةِ صَباحاً. أَتَعَلَّمُ القِراءَةَ وَالكِتابَةَ. أُحِبُّ مُعَلِّمَتي.',
                'difficulty_level' => 'CE1'
            ],
            [
                'title' => 'العائلة السعيدة',
                'content' => 'هذه عائِلَتي. أَبي وَأُمّي وَأَخي. نَحنُ عائِلَةٌ سَعيدَةٌ.',
                'difficulty_level' => 'CE1'
            ]
        ];

        foreach ($ce1Texts as $textData) {
            $text = Text::create($textData);
            // Associer à toutes les classes CE1
            $text->classrooms()->attach($ce1Classrooms->pluck('id'));
        }

        // Textes pour CE2 (niveau intermédiaire)
        $ce2Classrooms = $classrooms->filter(fn($c) => $c->level->name === 'CE2');
        $ce2Texts = [
            [
                'title' => 'الطفل والكتاب',
                'content' => 'يَقرَأُ الطِّفلُ كِتاباً جَديداً. الكِتابُ مُمتِعٌ وَمُفيدٌ. يَتَعَلَّمُ الطِّفلُ أَشياءَ كَثيرَةً مِنَ الكِتابِ.',
                'difficulty_level' => 'CE2'
            ],
            [
                'title' => 'في المدرسة',
                'content' => 'ذَهَبَ أَحمَدُ إِلى المَدرَسَةِ صَباحاً. التَقى بِأَصدِقائِهِ في الفَصلِ. دَرَسوا اللُّغَةَ العَرَبِيَّةَ وَالرِّياضِيّاتِ.',
                'difficulty_level' => 'CE2'
            ],
            [
                'title' => 'الفصول الأربعة',
                'content' => 'السَّنَةُ لَها أَربَعَةُ فُصولٍ. الرَّبيعُ وَالصَّيفُ وَالخَريفُ وَالشِّتاءُ. كُلُّ فَصلٍ لَهُ جَمالُهُ الخاصُّ.',
                'difficulty_level' => 'CE2'
            ],
            [
                'title' => 'النحلة المجتهدة',
                'content' => 'النَّحلَةُ تَعمَلُ بِجِدٍّ كُلَّ يَومٍ. تَجمَعُ الرَّحيقَ مِنَ الأَزهارِ. تَصنَعُ العَسَلَ اللَّذيذَ لِلنّاسِ.',
                'difficulty_level' => 'CE2'
            ],
            [
                'title' => 'الأصدقاء الثلاثة',
                'content' => 'كانَ هُناكَ ثَلاثَةُ أَصدِقاءَ يَلعَبونَ مَعاً. أَحمَدُ وَعَليٌّ وَفاطِمَةُ. يُحِبّونَ اللَّعِبَ في الحَديقَةِ وَالقِراءَةَ مَعاً.',
                'difficulty_level' => 'CE2'
            ],
            [
                'title' => 'رحلة إلى الغابة',
                'content' => 'ذَهَبَت العائِلَةُ في رِحلَةٍ إِلى الغابَةِ. شاهَدوا الأَشجارَ الكَبيرَةَ وَالطُّيورَ الجَميلَةَ. اِستَمتَعوا بِالهَواءِ النَّقِيِّ.',
                'difficulty_level' => 'CE2'
            ]
        ];

        foreach ($ce2Texts as $textData) {
            $text = Text::create($textData);
            // Associer à toutes les classes CE2
            $text->classrooms()->attach($ce2Classrooms->pluck('id'));
        }

        // Textes pour CM1 (niveau avancé)
        $cm1Classrooms = $classrooms->filter(fn($c) => $c->level->name === 'CM1');
        $cm1Texts = [
            [
                'title' => 'رحلة إلى البحر',
                'content' => 'سافَرَت العائِلَةُ إِلى شاطِئِ البَحرِ في عُطلَةِ الصَّيفِ. اِستَمتَعوا بِالسِّباحَةِ وَبِناءِ القِلاعِ الرَّمليَّةِ. كانَت رِحلَةً رائِعَةً لا تُنسى.',
                'difficulty_level' => 'CM1'
            ],
            [
                'title' => 'العلم والمعرفة',
                'content' => 'العِلمُ نورٌ يُضيءُ طَريقَ الإِنسانِ. بِالعِلمِ نَبني الحَضارَةَ وَنُطَوِّرُ المُجتَمَعَ. يَجِبُ عَلى كُلِّ إِنسانٍ أَن يَسعى لِطَلَبِ العِلمِ.',
                'difficulty_level' => 'CM1'
            ],
            [
                'title' => 'الطبيعة والبيئة',
                'content' => 'الطَّبيعَةُ هِيَ بَيتُنا الكَبيرُ. فيها الأَشجارُ وَالأَنهارُ وَالحَيواناتُ. يَجِبُ عَلَينا المُحافَظَةُ عَلَيها وَحِمايَتُها مِنَ التَّلوُّثِ.',
                'difficulty_level' => 'CM1'
            ],
            [
                'title' => 'الاختراعات العظيمة',
                'content' => 'عَبرَ التاريخِ، اختَرَعَ الإِنسانُ أَشياءَ عَظيمَةً. العَجلَةُ وَالطَّائِرَةُ وَالحاسوبُ. هذه الاختِراعاتُ غَيَّرَت حَياتَنا إِلى الأَفضَلِ.',
                'difficulty_level' => 'CM1'
            ],
            [
                'title' => 'قصة العصفور والنملة',
                'content' => 'في يَومٍ مِنَ أَيامِ الشِّتاءِ البارِدِ، طَلَبَ عُصفورٌ جائِعٌ مِن نَملَةٍ طَعاماً. قالَت النَّملَةُ: لقَد عَمِلتُ بِجِدٍّ في الصَّيفِ، وَأَنتَ لَعِبتَ. تَعَلَّمَ العُصفورُ دَرساً مُهِماً في الحَياةِ.',
                'difficulty_level' => 'CM1'
            ]
        ];

        foreach ($cm1Texts as $textData) {
            $text = Text::create($textData);
            // Associer à toutes les classes CM1
            $text->classrooms()->attach($cm1Classrooms->pluck('id'));
        }

        // Textes pour CM2 (niveau supérieur)
        $cm2Classrooms = $classrooms->filter(fn($c) => $c->level->name === 'CM2');
        $cm2Texts = [
            [
                'title' => 'التراث العربي',
                'content' => 'التُّراثُ العَرَبيُّ غَنيٌّ بِالقِيَمِ وَالتَّقاليدِ الأَصيلَةِ. يَشمَلُ الأَدَبَ وَالشِّعرَ وَالفُنونَ الشَّعبِيَّةَ. مِن واجِبِنا المُحافَظَةُ عَلى هذا التُّراثِ وَنَقلُهُ لِلأَجيالِ القادِمَةِ.',
                'difficulty_level' => 'CM2'
            ],
            [
                'title' => 'البيئة والطبيعة',
                'content' => 'البيئَةُ هِيَ كُلُّ ما يُحيطُ بِالإِنسانِ مِن هَواءٍ وَماءٍ وَتُربَةٍ وَنَباتاتٍ وَحَيَواناتٍ. يَجِبُ عَلَينا المُحافَظَةُ عَلى البيئَةِ وَحِمايَتُها مِنَ التَّلوُّثِ لِضَمانِ مُستَقبَلٍ أَفضَلَ.',
                'difficulty_level' => 'CM2'
            ],
            [
                'title' => 'الحضارة العربية الإسلامية',
                'content' => 'ازدَهَرَت الحَضارَةُ العَرَبِيَّةُ الإِسلامِيَّةُ عَبرَ قُرونٍ عَديدَةٍ. أَنتَجَت عُلماءَ عِظاماً في الطِّبِّ وَالرِّياضِيّاتِ وَالفِلسَفَةِ. وَأَسَّسَت جامِعاتٍ وَمَكتَباتٍ عَظيمَةً لا تَزالُ قائِمَةً حَتّى اليَومِ.',
                'difficulty_level' => 'CM2'
            ],
            [
                'title' => 'العلم والتكنولوجيا',
                'content' => 'في عَصرِنا الحَديثِ، تَتَطَوَّرُ التِّكنولوجيا بِسُرعَةٍ مُذهِلَةٍ. الحاسوبُ وَالإِنتَرنَت وَالهواتِفُ الذَّكِيَّةُ غَيَّرَت طَريقَةَ حَياتِنا وَتَعلُّمِنا وَتَواصُلِنا مَع العالَمِ.',
                'difficulty_level' => 'CM2'
            ],
            [
                'title' => 'قيم التعاون والتضامن',
                'content' => 'التَّعاوُنُ وَالتَّضامُنُ قِيَمٌ أَساسِيَّةٌ في بِناءِ المُجتَمَعِ الصَّالِحِ. عِندَما يَتَعاوَنُ النّاسُ وَيَتَضامَنونَ فيما بَينَهُم، يُمكِنُهُم تَحقيقُ أَهدافٍ عَظيمَةٍ وَبِناءُ مُستَقبَلٍ أَفضَلَ لِلجَميعِ.',
                'difficulty_level' => 'CM2'
            ],
            [
                'title' => 'رحلة عبر التاريخ',
                'content' => 'التاريخُ مِرآةٌ تُعَكِسُ ماضي الشُّعوبِ وَحَضاراتِها. مِن الفراعِنَةِ في مِصرَ إِلى الإغريقِ في اليونانِ، وَمِن الرومانِ إِلى الحَضارَةِ العَرَبِيَّةِ الإِسلامِيَّةِ. كُلُّ حَضارَةٍ تَرَكَت بَصمَةً في تاريخِ الإِنسانِيَّةِ.',
                'difficulty_level' => 'CM2'
            ]
        ];

        foreach ($cm2Texts as $textData) {
            $text = Text::create($textData);
            // Associer à toutes les classes CM2
            $text->classrooms()->attach($cm2Classrooms->pluck('id'));
        }

        // Créer quelques textes partagés entre plusieurs niveaux
        $sharedTexts = [
            [
                'title' => 'الصداقة',
                'content' => 'الصَّداقَةُ كَنزٌ ثَمينٌ. الصَّديقُ الحَقيقيُّ يَقِفُ بِجانِبِكَ في السَّرّاءِ وَالضَّرّاءِ.',
                'difficulty_level' => 'CE2',
                'classrooms' => $classrooms->filter(fn($c) => in_array($c->level->name, ['CE2', 'CM1']))->pluck('id')
            ],
            [
                'title' => 'الوطن',
                'content' => 'الوَطَنُ هُوَ الأَرضُ الَّتي وُلِدنا عَلَيها وَنَشَأنا فيها. نُحِبُّ وَطَنَنا وَنَعمَلُ مِن أَجلِ رُقِيِّهِ وَتَقَدُّمِهِ.',
                'difficulty_level' => 'CM1',
                'classrooms' => $classrooms->filter(fn($c) => in_array($c->level->name, ['CM1', 'CM2']))->pluck('id')
            ]
        ];

        foreach ($sharedTexts as $textData) {
            $classroomIds = $textData['classrooms'];
            unset($textData['classrooms']);
            
            $text = Text::create($textData);
            $text->classrooms()->attach($classroomIds);
        }

        $this->command->info('Textes créés et associés aux classes avec succès!');
    }
}
