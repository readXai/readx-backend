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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // CE1A, CE1B, CE2A, etc.
            $table->foreignId('level_id')->nullable()->constrained('levels')->onDelete('set null');
            $table->string('section', 1); // A, B, C, etc.
            $table->string('description')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['level_id', 'section']);
            $table->unique(['level_id', 'section']); // Une seule section A par niveau
        });

        // Insérer quelques classes par défaut
        $levels = \Illuminate\Support\Facades\DB::table('levels')->get();
        foreach ($levels as $level) {
            \Illuminate\Support\Facades\DB::table('classrooms')->insert([
                [
                    'name' => $level->name . 'A',
                    'level_id' => $level->id,
                    'section' => 'A',
                    'description' => 'Classe ' . $level->name . ' section A',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
