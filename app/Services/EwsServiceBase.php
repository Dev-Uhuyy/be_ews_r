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
    protected const SKS_PER_SEMESTER_MAX = 20;

    protected const SKS_PER_SEMESTER_11_14 = 24;

    public function updateStatus(AkademikMahasiswa $akademik): array
    {
        $jenjang = $this->resolveJenjang($akademik);
        $K = (int) $jenjang['kurikulum'];
        $sksTarget = (int) $jenjang['sks'];

        $this->updateNilaiDE($akademik, $sksTarget);
        $akademik->refresh();

        $status = $this->hitungStatus($akademik, $K, $sksTarget);
        $statusKelulusan = $this->hitungStatusKelulusan($akademik, $sksTarget);

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

    private function resolveJenjang(AkademikMahasiswa $akademik): array
    {
        $default = config('ews.jenjang_default');
        $gelar = $akademik->mahasiswa?->prodi?->gelar ?? $default;

        return config("ews.jenjang.{$gelar}") ?? config("ews.jenjang.{$default}");
    }

    private function updateNilaiDE(AkademikMahasiswa $akademik, int $sksTarget): void
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

        $maxSksNilaiD = $sksTarget * 0.05;
        $nilaiDMelebihiBatas = ($countMKNilaiD > 2) || ($totalSksNilaiD > $maxSksNilaiD);

        $akademik->update([
            'nilai_d_melebihi_batas' => $nilaiDMelebihiBatas ? 'yes' : 'no',
            'nilai_e' => $adaNilaiE ? 'yes' : 'no',
        ]);
    }

    private function hitungStatusKelulusan(AkademikMahasiswa $akademik, int $sksTarget): string
    {
        $ipkMemenuhi = $akademik->ipk > 2.0;
        $sksMemenuhi = $akademik->sks_lulus >= $sksTarget;
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

    private function hitungStatus(AkademikMahasiswa $akademik, int $K, int $sksTarget): string
    {
        $sksLulus = $akademik->sks_lulus ?? 0;
        $semesterAktif = $akademik->semester_aktif ?? 1;
        $sisaSks = max(0, $sksTarget - $sksLulus);

        $gradeCounts = $this->getMahasiswaGradeCounts($akademik->mahasiswa_id);
        $nilaiD = $gradeCounts->get('D', collect());
        $nilaiE = $gradeCounts->get('E', collect());

        $jumlahNilaiE = $nilaiE->count();
        $jumlahNilaiD = $nilaiD->count();

        // Batas semester per tier, diturunkan dari masa kurikulum K.
        $batasNormal = $K;
        $batasPerhatian = $K + 2;
        $batasKritis = 2 * $K;

        $sksBisaDiambilSDKritis = $this->hitungSksMaksBisaDiambil($semesterAktif, $batasKritis, $batasPerhatian);
        $sksBisaDiambilSDPerhatian = $this->hitungSksMaksBisaDiambil($semesterAktif, $batasPerhatian, $batasPerhatian);
        $sksBisaDiambilSDNormal = $this->hitungSksMaksBisaDiambil($semesterAktif, $batasNormal, $batasPerhatian);

        $isGenap = ($semesterAktif % 2 === 0);
        $isGanjil = ! $isGenap;

        if ($sksLulus >= $sksTarget) {
            return match (true) {
                $semesterAktif <= $batasNormal => 'tepat_waktu',
                $semesterAktif <= $batasPerhatian => 'normal',
                $semesterAktif <= $batasKritis => 'perhatian',
                default => 'kritis',
            };
        }

        if ($sisaSks > $sksBisaDiambilSDKritis) {
            return 'kritis';
        }

        if ($isGanjil && $semesterAktif === $batasKritis - 1) {
            if ($this->cekAdaEDMataKuliah($nilaiD, $nilaiE, $K, ganjil: true)) {
                return 'kritis';
            }
        } elseif ($isGenap && $semesterAktif === $batasKritis) {
            if ($this->cekAdaEDMataKuliah($nilaiD, $nilaiE, $K, ganjil: false)) {
                return 'kritis';
            }
        }

        if ($sisaSks > $sksBisaDiambilSDPerhatian) {
            return 'perhatian';
        }

        if ($isGanjil && $semesterAktif === $batasPerhatian - 1) {
            if ($this->cekAdaEDMataKuliah($nilaiD, $nilaiE, $K, ganjil: true)) {
                return 'perhatian';
            }
        } elseif ($isGenap && $semesterAktif === $batasPerhatian) {
            if ($this->cekAdaEDMataKuliah($nilaiD, $nilaiE, $K, ganjil: false)) {
                return 'perhatian';
            }
        }

        if ($sisaSks > $sksBisaDiambilSDNormal) {
            return 'normal';
        }

        if ($isGanjil && $semesterAktif === $batasNormal - 1) {
            if ($this->cekAdaEDMataKuliah($nilaiD, $nilaiE, $K, ganjil: true)) {
                return 'normal';
            }
        } elseif ($isGenap && $semesterAktif === $batasNormal) {
            if ($this->cekAdaEDMataKuliah($nilaiD, $nilaiE, $K, ganjil: false)) {
                return 'normal';
            }
        }

        $kondisiSksBiru = ($sisaSks <= $sksBisaDiambilSDNormal);

        if ($isGanjil && $semesterAktif === $batasNormal - 1) {
            if ($kondisiSksBiru && $jumlahNilaiE <= 0 && $jumlahNilaiD <= 1) {
                return 'tepat_waktu';
            }
        } elseif ($isGenap && $semesterAktif === $batasNormal) {
            if ($kondisiSksBiru && $jumlahNilaiE <= 0 && $jumlahNilaiD <= 1) {
                return 'tepat_waktu';
            }
        }

        return 'normal';
    }

    private function cekAdaEDMataKuliah($nilaiD, $nilaiE, int $K, bool $ganjil): bool
    {
        $semesters = $ganjil ? range(1, $K, 2) : range(2, $K, 2);

        foreach ($nilaiD as $grade) {
            if (in_array((int) $grade->semester, $semesters)) {
                return true;
            }
        }

        foreach ($nilaiE as $grade) {
            if (in_array((int) $grade->semester, $semesters)) {
                return true;
            }
        }

        return false;
    }

    private function hitungSksMaksBisaDiambil(int $semesterSekarang, int $semesterTarget, int $capCutoff): int
    {
        if ($semesterSekarang > $semesterTarget) {
            return 0;
        }

        $totalSks = 0;
        for ($smt = $semesterSekarang; $smt <= $semesterTarget; $smt++) {
            $totalSks += $smt <= $capCutoff ? self::SKS_PER_SEMESTER_MAX : self::SKS_PER_SEMESTER_11_14;
        }

        return $totalSks;
    }

    protected function getBaseQueryExcludeLulusDo(): Builder
    {
        return AkademikMahasiswa::with('mahasiswa.prodi')
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
