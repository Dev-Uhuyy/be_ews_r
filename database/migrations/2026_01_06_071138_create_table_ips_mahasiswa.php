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
        Schema::create('ips_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa', 'id')->onDelete('cascade');
            $table->decimal('ips_1', 4, 2);
            $table->decimal('ips_2', 4, 2);
            $table->decimal('ips_3', 4, 2);
            $table->decimal('ips_4', 4, 2);
            $table->decimal('ips_5', 4, 2);
            $table->decimal('ips_6', 4, 2);
            $table->decimal('ips_7', 4, 2);
            $table->decimal('ips_8', 4, 2);
            $table->decimal('ips_9', 4, 2);
            $table->decimal('ips_10', 4, 2);
            $table->decimal('ips_11', 4, 2);
            $table->decimal('ips_12', 4, 2);
            $table->decimal('ips_13', 4, 2);
            $table->decimal('ips_14', 4, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ips_mahasiswa');
    }
};
