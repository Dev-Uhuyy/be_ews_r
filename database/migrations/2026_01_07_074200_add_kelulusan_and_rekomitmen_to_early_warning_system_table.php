<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->enum('status_kelulusan', ['eligible', 'noneligible'])
                ->default('noneligible')
                ->after('status');

            $table->enum('status_rekomitmen', ['yes', 'no'])
                ->nullable()
                ->after('status_kelulusan');

            $table->string('link_rekomitmen')
                ->nullable()
                ->after('status_rekomitmen');
        });
    }

    public function down(): void
    {
        Schema::table('early_warning_system', function (Blueprint $table) {
            $table->dropColumn([
                'status_kelulusan',
                'status_rekomitmen',
                'link_rekomitmen'
            ]);
        });
    }
};
