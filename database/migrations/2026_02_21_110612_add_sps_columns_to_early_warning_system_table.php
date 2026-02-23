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
        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->enum('SPS1', ['yes', 'no'])->default('no')->after('link_rekomitmen');
            $table->enum('SPS2', ['yes', 'no'])->default('no')->after('SPS1');
            $table->enum('SPS3', ['yes', 'no'])->default('no')->after('SPS2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->dropColumn(['SPS1', 'SPS2', 'SPS3']);
        });
    }
};
