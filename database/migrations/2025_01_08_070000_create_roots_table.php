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
        Schema::create('roots', function (Blueprint $table) {
            $table->id();
            $table->string('root_text'); // ex: "ك ت ب"
            $table->string('root_normalized')->nullable(); // version normalisée
            $table->text('description')->nullable(); // description de la racine
            $table->timestamps();
            
            $table->index('root_text');
            $table->unique('root_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roots');
    }
};
