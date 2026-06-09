<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AkademikMahasiswa;
use App\Models\EarlyWarningSystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class EwsServiceBase
{
    protected const MAX_SKS_NILAI_D = 7.2;

    protected const SKS_TARGET = 144;

    protected const SKS_PER_SEMESTER_MAX = 20;

    protected const SKS_PER_SEMESTER_11_14 = 24;

    public function updateStatus(AkademikMahasiswa $akademik): array
    {
        $this->updateNilaiDE($akademik);
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

    private function updateNilaiDE(AkademikMahasiswa $akademik): void
    {
        $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->whereIn('khs1.id', function ($query) use ($akademik): void {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa as khs2')
                    ->where('khs2.mahasiswa_id', $akademik->mahasiswa_id)
                    ->groupBy('khs2.matakuliah_id');
            })
            ->where('khs1.mahasiswa_id', $akademik->mahasiswa_id)
            ->select('khs1.nilai_akhir_huruf', 'mata_kuliahs.sks')
            ->get();

        $totalSksNilaiD = 0;
        $countMKNilaiD = 0;
        $adaNilaiE = false;

        foreach ($latestKhs as $khs) {
            if ($khs->nilai_akhir_huruf === 'D') {
                $countMKNilaiD++;
                $totalSksNilaiD += $khs->sks;
            } elseif ($khs->nilai_akhir_huruf === 'E') {
                $adaNilaiE = true;
            }
        }

        $nilaiDMelebihiBatas = ($countMKNilaiD > 2) || ($totalSksNilaiD > self::MAX_SKS_NILAI_D);

        $akademik->update([
            'nilai_d_melebihi_batas' => $nilaiDMelebihiBatas ? 'yes' : 'no',
            'nilai_e' => $adaNilaiE ? 'yes' : 'no',
        ]);
    }

    private function hitungStatusKelulusan(AkademikMahasiswa $akademik): string
    {
        $ipkMemenuhi = $akademik->ipk > 2.0;
        $sksMemenuhi = $akademik->sks_lulus >= self::SKS_TARGET;
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

    private function getMahasiswaGradeCounts(int $mahasiswaId)
    {
        return DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->whereIn('khs1.id', function ($query) use ($mahasiswaId): void {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa as khs2')
                    ->where('khs2.mahasiswa_id', $mahasiswaId)
                    ->groupBy('khs2.matakuliah_id');
            })
            ->where('khs1.mahasiswa_id', $mahasiswaId)
            ->select(
                'khs1.nilai_akhir_huruf',
                'mata_kuliahs.semester'
            )
            ->get()
            ->groupBy('nilai_akhir_huruf');
    }

    private function hitungStatus(AkademikMahasiswa $akademik): string
    {
        $sksLulus = $akademik->sks_lulus ?? 0;
        $semesterAktif = $akademik->semester_aktif ?? 1;
        $sisaSks = max(0, self::SKS_TARGET - $sksLulus);

        $gradeCounts = $this->getMahasiswaGradeCounts($akademik->mahasiswa_id);
        $nilaiD = $gradeCounts->get('D', collect());
        $nilaiE = $gradeCounts->get('E', collect());

        $jumlahNilaiE = $nilaiE->count();
        $jumlahNilaiD = $nilaiD->count();

        $sksBisaDiambilSD14 = $this->hitungSksMaksBisaDiambil($semesterAktif, 14);
        $sksBisaDiambilSD10 = $this->hitungSksMaksBisaDiambil($semesterAktif, 10);
        $sksBisaDiambilSD8 = $this->hitungSksMaksBisaDiambil($semesterAktif, 8);

        $isGenap = ($semesterAktif % 2 === 0);
        $isGanjil = ! $isGenap;

        if ($sksLulus >= self::SKS_TARGET) {
            return match (true) {
                $semesterAktif <= 8 => 'tepat_waktu',
                $semesterAktif <= 10 => 'normal',
                $semesterAktif <= 14 => 'perhatian',
                default => 'kritis',
            };
        }

        if ($sisaSks > $sksBisaDiambilSD14) {
            return 'kritis';
        }

        if ($isGanjil && $semesterAktif === 13) {
            if ($this->cekAdaEDMataKuliahGanjil($akademik->mahasiswa_id, $nilaiD, $nilaiE)) {
                return 'kritis';
            }
        } elseif ($isGenap && $semesterAktif === 14) {
            if ($this->cekAdaEDMataKuliahGenap($akademik->mahasiswa_id, $nilaiD, $nilaiE)) {
                return 'kritis';
            }
        }

        if ($sisaSks > $sksBisaDiambilSD10) {
            return 'perhatian';
        }

        if ($isGanjil && $semesterAktif === 9) {
            if ($this->cekAdaEDMataKuliahGanjil($akademik->mahasiswa_id, $nilaiD, $nilaiE)) {
                return 'perhatian';
            }
        } elseif ($isGenap && $semesterAktif === 10) {
            if ($this->cekAdaEDMataKuliahGenap($akademik->mahasiswa_id, $nilaiD, $nilaiE)) {
                return 'perhatian';
            }
        }

        if ($sisaSks > $sksBisaDiambilSD8) {
            return 'normal';
        }

        if ($isGanjil && $semesterAktif === 7) {
            if ($this->cekAdaEDMataKuliahGanjil($akademik->mahasiswa_id, $nilaiD, $nilaiE)) {
                return 'normal';
            }
        } elseif ($isGenap && $semesterAktif === 8) {
            if ($this->cekAdaEDMataKuliahGenap($akademik->mahasiswa_id, $nilaiD, $nilaiE)) {
                return 'normal';
            }
        }

        $kondisiSksBiru = ($sisaSks <= $sksBisaDiambilSD8);

        if ($isGanjil && $semesterAktif === 7) {
            if ($kondisiSksBiru && $jumlahNilaiE <= 0 && $jumlahNilaiD <= 1) {
                return 'tepat_waktu';
            }
        } elseif ($isGenap && $semesterAktif === 8) {
            if ($kondisiSksBiru && $jumlahNilaiE <= 0 && $jumlahNilaiD <= 1) {
                return 'tepat_waktu';
            }
        }

        return 'normal';
    }

    private function cekAdaEDMataKuliahGanjil(int $mahasiswaId, $nilaiD, $nilaiE): bool
    {
        $ganjilSemesters = [1, 3, 5, 7];

        foreach ($nilaiD as $grade) {
            if (in_array((int) $grade->semester, $ganjilSemesters)) {
                return true;
            }
        }

        foreach ($nilaiE as $grade) {
            if (in_array((int) $grade->semester, $ganjilSemesters)) {
                return true;
            }
        }

        return false;
    }

    private function cekAdaEDMataKuliahGenap(int $mahasiswaId, $nilaiD, $nilaiE): bool
    {
        $genapSemesters = [2, 4, 6, 8];

        foreach ($nilaiD as $grade) {
            if (in_array((int) $grade->semester, $genapSemesters)) {
                return true;
            }
        }

        foreach ($nilaiE as $grade) {
            if (in_array((int) $grade->semester, $genapSemesters)) {
                return true;
            }
        }

        return false;
    }

    private function hitungSksMaksBisaDiambil(int $semesterSekarang, int $semesterTarget): int
    {
        if ($semesterSekarang > $semesterTarget) {
            return 0;
        }

        $totalSks = 0;
        for ($smt = $semesterSekarang; $smt <= $semesterTarget; $smt++) {
            $totalSks += $smt <= 10 ? self::SKS_PER_SEMESTER_MAX : self::SKS_PER_SEMESTER_11_14;
        }

        return $totalSks;
    }

    protected function getBaseQueryExcludeLulusDo(): Builder
    {
        return AkademikMahasiswa::with('mahasiswa')
            ->whereHas('mahasiswa', fn ($query) => $query->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")'));
    }

    abstract protected function getProdiId(): ?int;

    public function updateAllStatus(?int $prodiId = null): array
    {
        $totalProcessed = 0;
        $totalUpdated = 0;

        $query = $this->getBaseQueryExcludeLulusDo();
        $scopedProdiId = $prodiId ?? $this->getProdiId();

        if ($scopedProdiId) {
            $query->whereHas('mahasiswa', fn ($q) => $q->where('prodi_id', $scopedProdiId));
        }

        $query->chunk(100, function ($akademiks) use (&$totalProcessed, &$totalUpdated): void {
            foreach ($akademiks as $akademik) {
                try {
                    $this->updateStatus($akademik);
                    $totalUpdated++;
                } catch (\Exception $e) {
                    Log::error("Error updating EWS for akademik_id {$akademik->id}: ".$e->getMessage());
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
