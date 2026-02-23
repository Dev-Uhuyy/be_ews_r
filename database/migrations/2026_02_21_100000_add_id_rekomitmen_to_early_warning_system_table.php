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
            $table->string('id_rekomitmen', 50)
                ->nullable()
                ->unique()
                ->after('status_rekomitmen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->dropColumn('id_rekomitmen');
        });
    }
};
