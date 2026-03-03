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
        $ewsData = Illuminate\Support\Facades\DB::table('early_warning_system')
            ->whereNotNull('link_rekomitmen')
            ->orWhereNotNull('status_rekomitmen')
            ->orWhereNotNull('tanggal_pengajuan_rekomitmen')
            ->get();

        foreach ($ewsData as $data) {
            Illuminate\Support\Facades\DB::table('tindak_lanjuts')->insert([
                'id_ews' => $data->id,
                'kategori' => 'rekomitmen',
                'link' => $data->link_rekomitmen,
                'status' => $data->status_rekomitmen ?? 'belum diverifikasi',
                'tanggal_pengajuan' => $data->tanggal_pengajuan_rekomitmen,
                'created_at' => $data->created_at,
                'updated_at' => $data->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Illuminate\Support\Facades\DB::table('tindak_lanjuts')->truncate();
    }
};
