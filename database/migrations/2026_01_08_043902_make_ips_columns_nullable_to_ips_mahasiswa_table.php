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
        Schema::table('ips_mahasiswa', function (Blueprint $table) {
            $table->decimal('ips_1', 4, 2)->nullable()->change();
            $table->decimal('ips_2', 4, 2)->nullable()->change();
            $table->decimal('ips_3', 4, 2)->nullable()->change();
            $table->decimal('ips_4', 4, 2)->nullable()->change();
            $table->decimal('ips_5', 4, 2)->nullable()->change();
            $table->decimal('ips_6', 4, 2)->nullable()->change();
            $table->decimal('ips_7', 4, 2)->nullable()->change();
            $table->decimal('ips_8', 4, 2)->nullable()->change();
            $table->decimal('ips_9', 4, 2)->nullable()->change();
            $table->decimal('ips_10', 4, 2)->nullable()->change();
            $table->decimal('ips_11', 4, 2)->nullable()->change();
            $table->decimal('ips_12', 4, 2)->nullable()->change();
            $table->decimal('ips_13', 4, 2)->nullable()->change();
            $table->decimal('ips_14', 4, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ips_mahasiswa', function (Blueprint $table) {
            $table->decimal('ips_1', 4, 2)->nullable(false)->change();
            $table->decimal('ips_2', 4, 2)->nullable(false)->change();
            $table->decimal('ips_3', 4, 2)->nullable(false)->change();
            $table->decimal('ips_4', 4, 2)->nullable(false)->change();
            $table->decimal('ips_5', 4, 2)->nullable(false)->change();
            $table->decimal('ips_6', 4, 2)->nullable(false)->change();
            $table->decimal('ips_7', 4, 2)->nullable(false)->change();
            $table->decimal('ips_8', 4, 2)->nullable(false)->change();
            $table->decimal('ips_9', 4, 2)->nullable(false)->change();
            $table->decimal('ips_10', 4, 2)->nullable(false)->change();
            $table->decimal('ips_11', 4, 2)->nullable(false)->change();
            $table->decimal('ips_12', 4, 2)->nullable(false)->change();
            $table->decimal('ips_13', 4, 2)->nullable(false)->change();
            $table->decimal('ips_14', 4, 2)->nullable(false)->change();
        });
    }
};
