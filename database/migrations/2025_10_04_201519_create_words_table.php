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
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->string('word_normalized'); // Mot normalisé sans ال et harakat optionnelles
            
            // Colonnes pour la structure linguistique
            $table->foreignId('text_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('word_text'); // Texte du mot arabe avec vocalisation
            $table->integer('position')->default(0); // position dans le texte
            $table->boolean('is_compound')->default(false); // simple ou composé
            
            $table->timestamps();
            
            $table->index('word_normalized'); // Index pour recherche rapide
            $table->index('text_id'); // Index pour les requêtes par texte
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('words');
    }
};
