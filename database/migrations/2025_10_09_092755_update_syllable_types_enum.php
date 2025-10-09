<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pour SQLite, nous devons vider la table et recréer la colonne
        DB::table('word_syllables')->truncate();
        
        Schema::table('word_syllables', function (Blueprint $table) {
            $table->dropColumn('syllable_type');
        });
        
        Schema::table('word_syllables', function (Blueprint $table) {
            $table->enum('syllable_type', ['CV', 'CVC', 'CVCC', 'CVV', 'CVVC', 'V', 'VC'])->after('syllable_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('word_syllables', function (Blueprint $table) {
            $table->dropColumn('syllable_type');
        });
        
        Schema::table('word_syllables', function (Blueprint $table) {
            $table->enum('syllable_type', ['CV', 'CVC', 'CVCC', 'V'])->after('syllable_position');
        });
    }
};
