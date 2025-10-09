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
        Schema::create('word_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('word_id')->constrained()->onDelete('cascade');
            $table->foreignId('root_id')->nullable()->constrained()->onDelete('set null');
            $table->string('unit_text'); // texte de l'unité
            $table->string('unit_normalized')->nullable(); // version normalisée
            $table->enum('linguistic_type', ['اسم', 'فعل', 'حرف'])->nullable(); // type linguistique
            $table->integer('position'); // position dans le mot
            $table->timestamps();
            
            $table->index(['word_id', 'position']);
            $table->index('linguistic_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_units');
    }
};
