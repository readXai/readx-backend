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
        Schema::create('student_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('text_id')->constrained()->onDelete('cascade');
            $table->foreignId('word_id')->constrained()->onDelete('cascade');
            $table->enum('action_type', ['click', 'double_click', 'help_syllables', 'help_letters', 'help_image', 'toggle_vocalization']);
            $table->integer('read_count')->default(1); // Nombre de fois que l'élève a lu ce mot
            $table->json('metadata')->nullable(); // Données supplémentaires (temps passé, etc.)
            $table->timestamps();
            
            $table->index(['student_id', 'text_id']); // Index pour les statistiques
            $table->index(['student_id', 'word_id']); // Index pour compter les lectures par mot
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_interactions');
    }
};
