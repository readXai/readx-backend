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
        Schema::create('reading_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('text_id')->nullable()->constrained()->onDelete('cascade'); // Optionnel si session globale
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable(); // Durée en secondes
            $table->integer('words_read')->default(0); // Nombre de mots lus pendant la session
            $table->integer('help_requested')->default(0); // Nombre d'aides demandées
            $table->timestamps();
            
            $table->index(['student_id', 'start_time']); // Index pour les statistiques temporelles
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_sessions');
    }
};
