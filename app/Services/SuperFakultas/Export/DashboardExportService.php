<?php

namespace App\Services\SuperFakultas\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DashboardExportService
{
    use ExportFormatterTrait;

    /**
     * Sheet 1: Ringkasan Prodi (per prodi, match `dashboard.tabel_ringkasan_prodi[]`)
     * Sheet 2: Detail per Angkatan (per prodi+tahun, match `dashboard/detail.tahun_angkatan[]`)
     */
    public function exportDashboard()
    {
        $spreadsheet = new Spreadsheet;

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan Prodi');
        $headers1 = [
            'Kode Prodi', 'Nama Prodi',
            'Jmlh Mhs', 'Aktif', 'Cuti', 'Mangkir', 'DO', 'Tdk Aktif',
            'IPK Rata-rata',
            'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis',
            'Eligible', 'Tdk Eligible',
        ];
        $this->writeTitleBlock($sheet1, 'TABLE RINGKASAN MAHASISWA', 'Semua Program Studi', 'Semua Tahun Angkatan', count($headers1));
        $startRow = 6;
        $this->writeHeaderRow($sheet1, $startRow, $headers1);
        $startRow++;
        $sheet1->freezePane('A' . $startRow);

        foreach ($this->getRingkasanPerProdi() as $i => $row) {
            $sheet1->setCellValue('A' . $startRow, $row['kode_prodi']);
            $sheet1->setCellValue('B' . $startRow, $row['nama_prodi']);
            $sheet1->setCellValue('C' . $startRow, $row['jumlah_mahasiswa']);
            $sheet1->setCellValue('D' . $startRow, $row['jumlah_mahasiswa_aktif']);
            $sheet1->setCellValue('E' . $startRow, $row['jumlah_mahasiswa_cuti']);
            $sheet1->setCellValue('F' . $startRow, $row['jumlah_mahasiswa_mangkir']);
            $sheet1->setCellValue('G' . $startRow, $row['jumlah_do']);
            $sheet1->setCellValue('H' . $startRow, $row['jumlah_mahasiswa_tidak_aktif']);
            $sheet1->setCellValue('I' . $startRow, number_format((float) $row['ipk_rata_rata'], 2));
            $sheet1->setCellValue('J' . $startRow, $row['jumlah_tepat_waktu']);
            $sheet1->setCellValue('K' . $startRow, $row['jumlah_normal']);
            $sheet1->setCellValue('L' . $startRow, $row['jumlah_perhatian']);
            $sheet1->setCellValue('M' . $startRow, $row['jumlah_kritis']);
            $sheet1->setCellValue('N' . $startRow, $row['eligible']);
            $sheet1->setCellValue('O' . $startRow, $row['tidak_eligible']);
            $this->styleDataRow($sheet1, $startRow, count($headers1), $i % 2 === 1);
            $startRow++;
        }
        $this->autoSizeColumns($sheet1, count($headers1));

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Detail per Angkatan');
        $headers2 = [
            'Kode Prodi', 'Nama Prodi', 'Tahun Masuk',
            'Jumlah Mhs', 'Aktif', 'Cuti', 'Mangkir', 'DO', 'Tdk Aktif',
            'IPK Rata',
            'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis',
            'Eligible', 'Tdk Eligible',
        ];
        $this->writeTitleBlock($sheet2, 'DETAIL DASHBOARD SUPER FAKULTAS', 'Semua Program Studi', 'Breakdown per Tahun Angkatan', count($headers2));
        $startRow2 = 6;

        foreach (Prodi::orderBy('kode_prodi')->get() as $prodi) {
            $tahunData = $this->getDetailPerTahun($prodi->id);
            if ($tahunData->isEmpty()) {
                continue;
            }
            $this->writeSectionHeader($sheet2, $startRow2, $prodi->kode_prodi . '  –  ' . $prodi->nama, count($headers2));
            $startRow2++;
            $this->writeHeaderRowNoFreeze($sheet2, $startRow2, $headers2);
            $startRow2++;

            foreach ($tahunData as $i => $row) {
                $sheet2->setCellValue('A' . $startRow2, $prodi->kode_prodi);
                $sheet2->setCellValue('B' . $startRow2, $prodi->nama);
                $sheet2->setCellValue('C' . $startRow2, $row['tahun_masuk']);
                $sheet2->setCellValue('D' . $startRow2, $row['jumlah_mahasiswa']);
                $sheet2->setCellValue('E' . $startRow2, $row['mahasiswa_aktif']);
                $sheet2->setCellValue('F' . $startRow2, $row['jumlah_cuti']);
                $sheet2->setCellValue('G' . $startRow2, $row['jumlah_mangkir']);
                $sheet2->setCellValue('H' . $startRow2, $row['jumlah_do']);
                $sheet2->setCellValue('I' . $startRow2, $row['jumlah_tidak_aktif']);
                $sheet2->setCellValue('J' . $startRow2, number_format((float) $row['ipk_rata_rata'], 2));
                $sheet2->setCellValue('K' . $startRow2, $row['tepat_waktu']);
                $sheet2->setCellValue('L' . $startRow2, $row['normal']);
                $sheet2->setCellValue('M' . $startRow2, $row['perhatian']);
                $sheet2->setCellValue('N' . $startRow2, $row['kritis']);
                $sheet2->setCellValue('O' . $startRow2, $row['eligible']);
                $sheet2->setCellValue('P' . $startRow2, $row['tidak_eligible']);
                $this->styleDataRow($sheet2, $startRow2, count($headers2), $i % 2 === 1);
                $startRow2++;
            }
            $startRow2++;
        }
        $this->autoSizeColumns($sheet2, count($headers2));

        return $this->saveFile($spreadsheet, 'SuperFakultas_Ringkasan_Prodi_' . date('Y-m-d'));
    }

    /**
     * Single sheet: Detail per Angkatan per prodi (match `dashboard/detail.tahun_angkatan[]`)
     * Filter: prodi_id (optional) — kalau null, tampilkan semua prodi dalam section header
     */
    public function exportDashboardDetail($filters = [])
    {
        $prodiId = $filters['prodi_id'] ?? null;
        $prodiLabel = $prodiId ? (Prodi::find($prodiId)?->nama ?? '?') : 'Semua Prodi';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail per Angkatan');

        $headers = [
            'Tahun Masuk',
            'Jumlah Mhs', 'Aktif', 'Cuti', 'Mangkir', 'DO', 'Tdk Aktif',
            'IPK Rata',
            'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis',
            'Eligible', 'Tdk Eligible',
        ];
        $this->writeTitleBlock($sheet, 'DETAIL DASHBOARD SUPER FAKULTAS', 'Program Studi: ' . $prodiLabel, 'Breakdown per Tahun Angkatan', count($headers));

        $prodis = $prodiId ? Prodi::where('id', $prodiId)->get() : Prodi::orderBy('kode_prodi')->get();
        $startRow = 6;

        foreach ($prodis as $prodi) {
            $tahunData = $this->getDetailPerTahun($prodi->id);
            if ($tahunData->isEmpty()) {
                continue;
            }
            $this->writeSectionHeader($sheet, $startRow, $prodi->kode_prodi . '  –  ' . $prodi->nama, count($headers));
            $startRow++;
            $this->writeHeaderRowNoFreeze($sheet, $startRow, $headers);
            $startRow++;

            foreach ($tahunData as $i => $row) {
                $sheet->setCellValue('A' . $startRow, $row['tahun_masuk']);
                $sheet->setCellValue('B' . $startRow, $row['jumlah_mahasiswa']);
                $sheet->setCellValue('C' . $startRow, $row['mahasiswa_aktif']);
                $sheet->setCellValue('D' . $startRow, $row['jumlah_cuti']);
                $sheet->setCellValue('E' . $startRow, $row['jumlah_mangkir']);
                $sheet->setCellValue('F' . $startRow, $row['jumlah_do']);
                $sheet->setCellValue('G' . $startRow, $row['jumlah_tidak_aktif']);
                $sheet->setCellValue('H' . $startRow, number_format((float) $row['ipk_rata_rata'], 2));
                $sheet->setCellValue('I' . $startRow, $row['tepat_waktu']);
                $sheet->setCellValue('J' . $startRow, $row['normal']);
                $sheet->setCellValue('K' . $startRow, $row['perhatian']);
                $sheet->setCellValue('L' . $startRow, $row['kritis']);
                $sheet->setCellValue('M' . $startRow, $row['eligible']);
                $sheet->setCellValue('N' . $startRow, $row['tidak_eligible']);
                $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
                $startRow++;
            }
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'SuperFakultas_Dashboard_Detail_' . date('Y-m-d'));
    }

    // ── Queries (3-query pattern duplicate dari SuperFakultasDashboardService & DetailDashboardService) ─

    /**
     * Per prodi aggregation. Return list of {kode_prodi, nama_prodi, 13 metrik}.
     * Mirrors `SuperFakultasDashboardService::getTabelRingkasanProdi()`.
     */
    private function getRingkasanPerProdi(): array
    {
        $prodis = Prodi::orderBy('kode_prodi')->get();
        $prodiIds = $prodis->pluck('id')->toArray();

        $nonDoStats = AkademikMahasiswa::select(
            'mahasiswa.prodi_id',
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif"   THEN 1 ELSE 0 END) as jumlah_mahasiswa_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti"    THEN 1 ELSE 0 END) as jumlah_mahasiswa_cuti'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mahasiswa_mangkir'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as jumlah_tepat_waktu'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "normal"      THEN 1 ELSE 0 END) as jumlah_normal'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian"   THEN 1 ELSE 0 END) as jumlah_perhatian'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis"      THEN 1 ELSE 0 END) as jumlah_kritis'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible"    THEN 1 ELSE 0 END) as eligible'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")')
            ->groupBy('mahasiswa.prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $doStats = AkademikMahasiswa::select(
            'mahasiswa.prodi_id',
            DB::raw('COUNT(*) as jumlah_do')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "do"')
            ->groupBy('mahasiswa.prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $tidakAktifStats = AkademikMahasiswa::select(
            'mahasiswa.prodi_id',
            DB::raw('COUNT(*) as jumlah_mahasiswa_tidak_aktif')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereIn('mahasiswa.prodi_id', $prodiIds)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "tidak_aktif"')
            ->groupBy('mahasiswa.prodi_id')
            ->get()
            ->keyBy('prodi_id');

        $result = [];
        foreach ($prodis as $prodi) {
            $non = $nonDoStats->get($prodi->id);
            $do = $doStats->get($prodi->id);
            $tdk = $tidakAktifStats->get($prodi->id);
            $result[] = [
                'kode_prodi' => $prodi->kode_prodi,
                'nama_prodi' => $prodi->nama,
                'jumlah_mahasiswa' => $non->jumlah_mahasiswa ?? 0,
                'jumlah_mahasiswa_aktif' => $non->jumlah_mahasiswa_aktif ?? 0,
                'jumlah_mahasiswa_cuti' => $non->jumlah_mahasiswa_cuti ?? 0,
                'jumlah_mahasiswa_mangkir' => $non->jumlah_mahasiswa_mangkir ?? 0,
                'jumlah_do' => $do->jumlah_do ?? 0,
                'jumlah_mahasiswa_tidak_aktif' => $tdk->jumlah_mahasiswa_tidak_aktif ?? 0,
                'ipk_rata_rata' => $non->ipk_rata_rata ?? 0,
                'jumlah_tepat_waktu' => $non->jumlah_tepat_waktu ?? 0,
                'jumlah_normal' => $non->jumlah_normal ?? 0,
                'jumlah_perhatian' => $non->jumlah_perhatian ?? 0,
                'jumlah_kritis' => $non->jumlah_kritis ?? 0,
                'eligible' => $non->eligible ?? 0,
                'tidak_eligible' => $non->tidak_eligible ?? 0,
            ];
        }
        return $result;
    }

    /**
     * Per (prodi, tahun) aggregation. Return list of 14-field row.
     * Mirrors `DetailDashboardService::getDetailDashboard()`.
     */
    private function getDetailPerTahun(int $prodiId)
    {
        $tahunData = AkademikMahasiswa::select(
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif"   THEN 1 ELSE 0 END) as mahasiswa_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti"    THEN 1 ELSE 0 END) as jumlah_cuti'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mangkir'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as tepat_waktu'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "normal"      THEN 1 ELSE 0 END) as normal'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian"   THEN 1 ELSE 0 END) as perhatian'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis"      THEN 1 ELSE 0 END) as kritis'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible"    THEN 1 ELSE 0 END) as eligible'),
            DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")')
            ->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get()
            ->keyBy('tahun_masuk');

        $doData = AkademikMahasiswa::select(
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(*) as jumlah_do')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "do"')
            ->groupBy('akademik_mahasiswa.tahun_masuk')
            ->get()
            ->keyBy('tahun_masuk');

        $tidakAktifData = AkademikMahasiswa::select(
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(*) as jumlah_tidak_aktif')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) = "tidak_aktif"')
            ->groupBy('akademik_mahasiswa.tahun_masuk')
            ->get()
            ->keyBy('tahun_masuk');

        return $tahunData->map(function ($item) use ($doData, $tidakAktifData) {
            return [
                'tahun_masuk' => $item->tahun_masuk,
                'jumlah_mahasiswa' => $item->jumlah_mahasiswa,
                'mahasiswa_aktif' => $item->mahasiswa_aktif,
                'jumlah_cuti' => $item->jumlah_cuti,
                'jumlah_mangkir' => $item->jumlah_mangkir,
                'jumlah_do' => $doData->get($item->tahun_masuk)->jumlah_do ?? 0,
                'jumlah_tidak_aktif' => $tidakAktifData->get($item->tahun_masuk)->jumlah_tidak_aktif ?? 0,
                'ipk_rata_rata' => $item->ipk_rata_rata,
                'tepat_waktu' => $item->tepat_waktu,
                'normal' => $item->normal,
                'perhatian' => $item->perhatian,
                'kritis' => $item->kritis,
                'eligible' => $item->eligible ?? 0,
                'tidak_eligible' => $item->tidak_eligible ?? 0,
            ];
        })->values();
    }
}
