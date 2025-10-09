<?php

/**
 * Script pour créer des images placeholder pour les tests
 */

$imageDir = __DIR__ . '/storage/app/public/images';
$directories = ['original', 'thumbnails', 'previews', 'mobile'];

// Créer les répertoires s'ils n'existent pas
foreach ($directories as $dir) {
    $fullPath = $imageDir . '/' . $dir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
}

// Images à créer
$images = [
    'book.jpg' => ['width' => 400, 'height' => 300, 'color' => [70, 130, 180], 'text' => 'كتاب'],
    'school.jpg' => ['width' => 400, 'height' => 300, 'color' => [34, 139, 34], 'text' => 'مدرسة'],
    'student.jpg' => ['width' => 400, 'height' => 300, 'color' => [255, 140, 0], 'text' => 'طالب'],
    'house.jpg' => ['width' => 400, 'height' => 300, 'color' => [220, 20, 60], 'text' => 'بيت'],
    'garden.jpg' => ['width' => 400, 'height' => 300, 'color' => [50, 205, 50], 'text' => 'حديقة']
];

foreach ($images as $filename => $config) {
    // Créer l'image originale
    $image = imagecreate($config['width'], $config['height']);
    $bgColor = imagecolorallocate($image, $config['color'][0], $config['color'][1], $config['color'][2]);
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    // Ajouter le texte au centre
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($config['text']);
    $textHeight = imagefontheight($fontSize);
    $x = ($config['width'] - $textWidth) / 2;
    $y = ($config['height'] - $textHeight) / 2;
    
    imagestring($image, $fontSize, $x, $y, $config['text'], $textColor);
    
    // Sauvegarder dans tous les répertoires
    foreach ($directories as $dir) {
        $filePath = $imageDir . '/' . $dir . '/' . $filename;
        imagejpeg($image, $filePath, 80);
        echo "Créé: $filePath\n";
    }
    
    imagedestroy($image);
}

echo "Images placeholder créées avec succès!\n";
