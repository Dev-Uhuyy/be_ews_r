<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prodi_user', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto increment (PK)

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('prodi_id')
                ->constrained('prodi')
                ->cascadeOnDelete();

            $table->timestamps(); // created_at, updated_at

            // Opsional tapi disarankan: cegah duplikasi user-prodi
            $table->unique(['user_id', 'prodi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prodi_user');
    }
};
