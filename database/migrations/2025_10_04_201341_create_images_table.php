<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('image_path'); // Chemin vers le fichier image
            $table->string('description')->nullable(); // Description optionnelle de l'image
            
            // Chemins des différentes versions optimisées
            $table->string('thumbnail_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->string('mobile_path')->nullable();
            
            // Métadonnées du fichier original
            $table->string('original_name')->nullable();
            $table->string('filename')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
