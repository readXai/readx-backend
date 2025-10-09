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
            $table->timestamps();
        });

        // Insérer les niveaux par défaut
        \Illuminate\Support\Facades\DB::table('levels')->insert([
            ['name' => 'CE1', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'CE2', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'CM1', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'CM2', 'created_at' => now(), 'updated_at' => now()],
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
