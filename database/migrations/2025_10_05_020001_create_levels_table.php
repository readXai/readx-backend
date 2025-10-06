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
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // CE1, CE2, CM1, CM2
            $table->string('description')->nullable();
            $table->integer('order'); // Pour l'ordre d'affichage (1=CE1, 2=CE2, etc.)
            $table->timestamps();
        });

        // Insérer les niveaux par défaut
        \Illuminate\Support\Facades\DB::table('levels')->insert([
            ['name' => 'CE1', 'description' => 'Cours Élémentaire 1ère année', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'CE2', 'description' => 'Cours Élémentaire 2ème année', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'CM1', 'description' => 'Cours Moyen 1ère année', 'order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'CM2', 'description' => 'Cours Moyen 2ème année', 'order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
