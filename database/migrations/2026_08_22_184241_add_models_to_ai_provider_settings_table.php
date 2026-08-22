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
        Schema::table('ai_provider_settings', function (Blueprint $table) {
            $table->string('text_model')->nullable()->after('text_provider');
            $table->string('image_model')->nullable()->after('image_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_provider_settings', function (Blueprint $table) {
            $table->dropColumn(['text_model', 'image_model']);
        });
    }
};
