<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

Route::get('/', function () {
    return view('welcome');
});

// Route pour servir les images avec les bons headers CORS
Route::get('/images/{type}/{filename}', function ($type, $filename) {
    $allowedTypes = ['original', 'thumbnails', 'previews', 'mobile'];
    
    if (!in_array($type, $allowedTypes)) {
        abort(404);
    }
    
    $path = "images/{$type}/{$filename}";
    
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    
    $file = Storage::disk('public')->get($path);
    $fullPath = Storage::disk('public')->path($path);
    $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';
    
    return response($file, 200)
        ->header('Content-Type', $mimeType)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', '*')
        ->header('Cache-Control', 'public, max-age=3600');
})->where('filename', '.*');
