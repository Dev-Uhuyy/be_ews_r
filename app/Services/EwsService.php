<?php

namespace App\Services;

use App\Models\AkademikMahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\KhsKrsMahasiswa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EwsService
{
    /**
     * Update status EWS untuk satu akademik mahasiswa
     */
    public function updateStatus(AkademikMahasiswa $akademik)
    {
        // Update nilai_d_melebihi_batas dan nilai_e terlebih dahulu dari KHS terbaru
        $this->updateNilaiDE($akademik);

        // Reload akademik mahasiswa untuk dapat nilai_d_melebihi_batas dan nilai_e terbaru
        $akademik->refresh();

        $status = $this->hitungStatus($akademik);
        $statusKelulusan = $this->hitungStatusKelulusan($akademik);

        EarlyWarningSystem::updateOrCreate(
            ['akademik_mahasiswa_id' => $akademik->id],
            [
                'status' => $status,
                'status_kelulusan' => $statusKelulusan,
            ]
        );

        return [
            'status' => $status,
            'status_kelulusan' => $statusKelulusan,
        ];
    }

    /**
     * Update nilai_d_melebihi_batas dan nilai_e berdasarkan KHS terakhir per mata kuliah
     */
    private function updateNilaiDE(AkademikMahasiswa $akademik)
    {
        // Get nilai terakhir per mata kuliah (ID terbesar)
        $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->whereIn('khs1.id', function($query) use ($akademik) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa as khs2')
                    ->where('khs2.mahasiswa_id', $akademik->mahasiswa_id)
                    ->groupBy('khs2.matakuliah_id');
            })
            ->where('khs1.mahasiswa_id', $akademik->mahasiswa_id)
            ->select('khs1.nilai_akhir_huruf', 'mata_kuliahs.sks')
            ->get();

        $totalSksNilaiD = 0;
        $adaNilaiE = false;

        foreach ($latestKhs as $khs) {
            if ($khs->nilai_akhir_huruf === 'D') {
                $totalSksNilaiD += $khs->sks;
            } elseif ($khs->nilai_akhir_huruf === 'E') {
                $adaNilaiE = true;
            }
        }

        // Cek apakah nilai D melebihi batas 5% dari SKS lulus
        $maxSksNilaiD = $akademik->sks_lulus * 0.05; // 5% dari SKS lulus
        $nilaiDMelebihiBatas = $totalSksNilaiD > $maxSksNilaiD;

        // Update akademik mahasiswa
        $akademik->update([
            'nilai_d_melebihi_batas' => $nilaiDMelebihiBatas ? 'yes' : 'no',
            'nilai_e' => $adaNilaiE ? 'yes' : 'no',
        ]);
    }

    /**
     * Hitung status kelulusan (eligible/noneligible)
     */
    private function hitungStatusKelulusan(AkademikMahasiswa $akademik)
    {
        // Eligible: IPK > 2.0, SKS lulus >= 144, semua MK wajib selesai,
        // TIDAK ada nilai E, dan nilai D tidak melebihi batas 5% dari SKS lulus
        $ipkMemenuhi = $akademik->ipk > 2.0;
        $sksMemenuhi = $akademik->sks_lulus >= 144;
        $mkNasionalSelesai = ($akademik->mk_nasional === 'yes');
        $mkFakultasSelesai = ($akademik->mk_fakultas === 'yes');
        $mkProdiSelesai = ($akademik->mk_prodi === 'yes');
        $nilaiDTidakMelebihiBatas = ($akademik->nilai_d_melebihi_batas === 'no');
        $tidakAdaNilaiE = ($akademik->nilai_e === 'no');

        if ($ipkMemenuhi && $sksMemenuhi && $mkNasionalSelesai && $mkFakultasSelesai && $mkProdiSelesai && $nilaiDTidakMelebihiBatas && $tidakAdaNilaiE) {
            return 'eligible';
        }

        return 'noneligible';
    }

    /**
     * Hitung status EWS (tepat_waktu, normal, perhatian, kritis)
     * Berdasarkan logic dari Python
     */
    private function hitungStatus(AkademikMahasiswa $akademik)
    {
        $sksLulus = $akademik->sks_lulus ?? 0;
        $semesterAktif = $akademik->semester_aktif ?? 1;
        $sisaSks = max(0, 144 - $sksLulus); // Tidak boleh negatif

        // Hitung jumlah nilai E dan D
        $jumlahNilaiE = KhsKrsMahasiswa::where('mahasiswa_id', $akademik->mahasiswa_id)
            ->where('nilai_akhir_huruf', 'E')
            ->count();

        $jumlahNilaiD = KhsKrsMahasiswa::where('mahasiswa_id', $akademik->mahasiswa_id)
            ->where('nilai_akhir_huruf', 'D')
            ->count();

        // Hitung SKS maksimal yang bisa diambil
        $sksBisaDiambilSD14 = $this->hitungSksMaksBisaDiambil($semesterAktif, 14);
        $sksBisaDiambilSD10 = $this->hitungSksMaksBisaDiambil($semesterAktif, 10);
        $sksBisaDiambilSD8 = $this->hitungSksMaksBisaDiambil($semesterAktif, 8);

        $isGenap = ($semesterAktif % 2 == 0);
        $isGanjil = !$isGenap;

        // Jika sudah lulus (SKS >= 144), langsung tepat_waktu jika semester <= 8
        if ($sksLulus >= 144) {
            if ($semesterAktif <= 8) {
                return 'tepat_waktu';
            } elseif ($semesterAktif <= 10) {
                return 'normal';
            } elseif ($semesterAktif <= 14) {
                return 'perhatian';
            } else {
                return 'kritis';
            }
        }

        // --- PRIORITAS 1: KRITIS (MERAH - DO/7 Tahun) ---
        if ($sisaSks > $sksBisaDiambilSD14) {
            return 'kritis';
        }

        // Kondisi nilai/NFU kritis (semester 13 & 14)
        if ($isGanjil && $semesterAktif == 13) {
            // Simplified: cek ada E/D di mata kuliah ganjil
            $adaEDGanjil = $this->cekAdaEDMataKuliahGanjil($akademik->mahasiswa_id);
            if ($adaEDGanjil) {
                return 'kritis';
            }
        } elseif ($isGenap && $semesterAktif == 14) {
            $adaEDGenap = $this->cekAdaEDMataKuliahGenap($akademik->mahasiswa_id);
            if ($adaEDGenap) {
                return 'kritis';
            }
        }

        // --- PRIORITAS 2: PERHATIAN (KUNING - 5 Tahun) ---
        if ($sisaSks > $sksBisaDiambilSD10) {
            return 'perhatian';
        }

        // Kondisi nilai/NFU perhatian (semester 9 & 10)
        if ($isGanjil && $semesterAktif == 9) {
            $adaEDGanjil = $this->cekAdaEDMataKuliahGanjil($akademik->mahasiswa_id);
            if ($adaEDGanjil) {
                return 'perhatian';
            }
        } elseif ($isGenap && $semesterAktif == 10) {
            $adaEDGenap = $this->cekAdaEDMataKuliahGenap($akademik->mahasiswa_id);
            if ($adaEDGenap) {
                return 'perhatian';
            }
        }

        // --- PRIORITAS 3: NORMAL (HIJAU - 4 Tahun) ---
        if ($sisaSks > $sksBisaDiambilSD8) {
            return 'normal';
        }

        // Kondisi nilai/NFU normal (semester 7 & 8)
        if ($isGanjil && $semesterAktif == 7) {
            $adaEDGanjil = $this->cekAdaEDMataKuliahGanjil($akademik->mahasiswa_id);
            if ($adaEDGanjil) {
                return 'normal';
            }
        } elseif ($isGenap && $semesterAktif == 8) {
            $adaEDGenap = $this->cekAdaEDMataKuliahGenap($akademik->mahasiswa_id);
            if ($adaEDGenap) {
                return 'normal';
            }
        }

        // --- PRIORITAS 4: TEPAT WAKTU (BIRU - Lulus 4 Tahun) ---
        $kondisiSksBiru = ($sisaSks <= $sksBisaDiambilSD8);

        if ($isGanjil && $semesterAktif == 7) {
            if ($kondisiSksBiru && $jumlahNilaiE <= 0 && $jumlahNilaiD <= 1) {
                return 'tepat_waktu';
            }
        } elseif ($isGenap && $semesterAktif == 8) {
            if ($kondisiSksBiru && $jumlahNilaiE <= 0 && $jumlahNilaiD <= 1) {
                return 'tepat_waktu';
            }
        }

        // Default
        return 'normal';
    }

    /**
     * Hitung total SKS maksimal yang bisa diambil dari semester sekarang hingga target
     */
    private function hitungSksMaksBisaDiambil($semesterSekarang, $semesterTarget)
    {
        if ($semesterSekarang > $semesterTarget) {
            return 0;
        }

        $totalSks = 0;
        for ($smt = $semesterSekarang; $smt <= $semesterTarget; $smt++) {
            if ($smt <= 10) {
                $totalSks += 20;
            } else {
                $totalSks += 24;
            }
        }

        return $totalSks;
    }

    /**
     * Cek apakah ada nilai E/D di mata kuliah semester ganjil
     */
    private function cekAdaEDMataKuliahGanjil($mahasiswaId)
    {
        return KhsKrsMahasiswa::where('mahasiswa_id', $mahasiswaId)
            ->whereHas('mata_kuliah', function ($query) {
                $query->whereIn('semester', [1, 3, 5, 7]);
            })
            ->whereIn('nilai_akhir_huruf', ['E', 'D'])
            ->exists();
    }

    /**
     * Cek apakah ada nilai E/D di mata kuliah semester genap
     */
    private function cekAdaEDMataKuliahGenap($mahasiswaId)
    {
        return KhsKrsMahasiswa::where('mahasiswa_id', $mahasiswaId)
            ->whereHas('mata_kuliah', function ($query) {
                $query->whereIn('semester', [2, 4, 6, 8]);
            })
            ->whereIn('nilai_akhir_huruf', ['E', 'D'])
            ->exists();
    }

    /**
     * Batch update untuk semua mahasiswa (untuk background job)
     */
    public function updateAllStatus()
    {
        $totalProcessed = 0;
        $totalUpdated = 0;

        // Exclude mahasiswa yang sudah lulus dan DO
        AkademikMahasiswa::with('mahasiswa')
            ->whereHas('mahasiswa', function($query) {
                $query->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")');
            })
            ->chunk(100, function ($akademiks) use (&$totalProcessed, &$totalUpdated) {
                foreach ($akademiks as $akademik) {
                    try {
                        $this->updateStatus($akademik);
                        $totalUpdated++;
                    } catch (\Exception $e) {
                        Log::error("Error updating EWS for akademik_id {$akademik->id}: " . $e->getMessage());
                    }
                    $totalProcessed++;
                }
            });

        return [
            'total_processed' => $totalProcessed,
            'total_updated' => $totalUpdated,
        ];
    }
}
