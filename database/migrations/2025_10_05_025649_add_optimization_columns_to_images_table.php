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
        Schema::table('images', function (Blueprint $table) {
            // Chemins des différentes versions optimisées
            $table->string('thumbnail_path')->nullable()->after('image_path');
            $table->string('preview_path')->nullable()->after('thumbnail_path');
            $table->string('mobile_path')->nullable()->after('preview_path');
            
            // Métadonnées du fichier original
            $table->string('original_name')->nullable()->after('mobile_path');
            $table->string('filename')->nullable()->after('original_name');
            $table->bigInteger('file_size')->nullable()->after('filename');
            $table->string('mime_type')->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn([
                'thumbnail_path',
                'preview_path', 
                'mobile_path',
                'original_name',
                'filename',
                'file_size',
                'mime_type'
            ]);
        });
    }
};
