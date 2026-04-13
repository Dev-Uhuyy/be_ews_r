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
        Schema::create('early_warning_system', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akademik_mahasiswa_id')->constrained('akademik_mahasiswa', 'id')->cascadeOnDelete();
            $table->enum('status', ['tepat_waktu', 'normal', 'perhatian', 'kritis'])->default('tepat_waktu');
            $table->string('status_kelulusan', 50)->nullable();
            $table->enum('SPS1', ['yes', 'no'])->default('no');
            $table->enum('SPS2', ['yes', 'no'])->default('no');
            $table->enum('SPS3', ['yes', 'no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('early_warning_system');
    }
};
