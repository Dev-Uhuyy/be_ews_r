<?php

namespace App\Services\Mahasiswa;

use App\Models\Mahasiswa;
use App\Models\KhsKrsMahasiswa;

class KhsKrsService
{
    public function getKhsKrsMahasiswa($userId, $perPage = 15, $page = 1)
    {
        // Get mahasiswa by user_id
        $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

        if (!$mahasiswa) {
            return null;
        }

        // Get all KHS KRS with relationships
        $allKhs = KhsKrsMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->with(['mata_kuliah', 'kelompok_mata_kuliah'])
            ->orderBy('id', 'asc')
            ->get();

        // Group by mata kuliah and keep only:
        // - If only taken once (status 'B'), keep that one
        // - If taken multiple times (has 'U'), keep the latest 'U' (last attempt)
        $khsGrouped = [];
        $khsStatus = []; // Track if MK has been taken multiple times

        foreach ($allKhs as $khs) {
            if ($khs->mata_kuliah) {
                $mkId = $khs->matakuliah_id;

                // Track how many times this MK was taken
                if (!isset($khsStatus[$mkId])) {
                    $khsStatus[$mkId] = ['count' => 0, 'has_ulang' => false];
                }
                $khsStatus[$mkId]['count']++;
                if ($khs->status === 'U') {
                    $khsStatus[$mkId]['has_ulang'] = true;
                }

                // Always replace with the latest one (since we ordered by id asc)
                // This will be either the only 'B' or the last 'U'
                $khsGrouped[$mkId] = $khs;
            }
        }

        // Convert to array (simplified for list)
        $khsKrsList = [];
        foreach ($khsGrouped as $mkId => $khs) {
            $khsKrsList[] = [
                'id' => $khs->id,
                'kode_matkul' => $khs->mata_kuliah->kode,
                'nama_matkul' => $khs->mata_kuliah->name,
                'sks' => $khs->mata_kuliah->sks,
                'nilai_huruf' => $khs->nilai_akhir_huruf,
            ];
        }

        // Sort by id
        usort($khsKrsList, function($a, $b) {
            return $a['id'] - $b['id'];
        });

        // Pagination
        $total = count($khsKrsList);
        $lastPage = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($khsKrsList, $offset, $perPage);

        return [
            'data' => $paginatedData,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ]
        ];
    }

    public function getDetailKhsKrs($userId, $khsKrsId)
    {
        // Get mahasiswa by user_id
        $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

        if (!$mahasiswa) {
            return ['error' => 'Mahasiswa tidak ditemukan'];
        }

        // Get KHS KRS by ID with ownership validation
        $khsKrs = KhsKrsMahasiswa::where('id', $khsKrsId)
            ->where('mahasiswa_id', $mahasiswa->id) // Validasi ownership
            ->with(['mata_kuliah', 'kelompok_mata_kuliah.dosen_pengampu.user'])
            ->first();

        if (!$khsKrs) {
            return ['error' => 'Data KHS tidak ditemukan atau bukan milik Anda'];
        }

        // Build detailed response
        $semester = isset($khsKrs->semester_ambil) ? $khsKrs->semester_ambil : ($khsKrs->mata_kuliah ? $khsKrs->mata_kuliah->semester : null);

        $detailData = [
            'id' => $khsKrs->id,
            'mata_kuliah' => [
                'id' => $khsKrs->mata_kuliah->id,
                'kode' => $khsKrs->mata_kuliah->kode,
                'nama' => $khsKrs->mata_kuliah->name,
                'sks' => $khsKrs->mata_kuliah->sks,
                'semester' => $khsKrs->mata_kuliah->semester,
                'tipe_mk' => $khsKrs->mata_kuliah->tipe_mk,
            ],
            'kelompok' => $khsKrs->kelompok_mata_kuliah ? [
                'id' => $khsKrs->kelompok_mata_kuliah->id,
                'kode' => $khsKrs->kelompok_mata_kuliah->kode,
                'dosen_pengampu' => $khsKrs->kelompok_mata_kuliah->dosen_pengampu && $khsKrs->kelompok_mata_kuliah->dosen_pengampu->user
                    ? $khsKrs->kelompok_mata_kuliah->dosen_pengampu->user->name
                    : null,
            ] : null,
            'semester_ambil' => $semester,
            'status' => $khsKrs->status,
            'status_display' => $khsKrs->status === 'B' ? 'Baru' : 'Ulang',
            'absen' => $khsKrs->absen,
            'nilai' => [
                'uts' => $khsKrs->nilai_uts,
                'uas' => $khsKrs->nilai_uas,
                'akhir_angka' => $khsKrs->nilai_akhir_angka,
                'akhir_huruf' => $khsKrs->nilai_akhir_huruf,
            ],
        ];

        return $detailData;
    }
}
