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
        Schema::create('word_syllables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_id')->constrained('words')->onDelete('cascade');
            $table->string('syllable_text', 20); // Plus d'espace pour l'arabe
            $table->tinyInteger('syllable_position');
            $table->string('syllable_type', 10); // Plus flexible que enum pour PostgreSQL
            $table->boolean('is_stressed')->default(false);
            $table->boolean('is_inferred')->default(false); // Pour mots non vocalisés
            $table->string('suggestion', 30)->nullable(); // Suggestions pédagogiques
            $table->json('metadata')->nullable(); // Métadonnées JSONB pour PostgreSQL
            $table->timestamps();
            
            $table->index('word_id');
            $table->index(['word_id', 'syllable_position']);
            $table->index('syllable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_syllables');
    }
};
