<?php

namespace App\Services\Admin\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DashboardExportService
{
    use ExportFormatterTrait;

    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    /**
     * Export Admin Dashboard — Tabel Ringkasan per Tahun Masuk (match `dashboard.tabel_ringkasan_prodi.tahun[]`)
     * 14 kolom: tahun_masuk + 13 metrik (jumlah_mahasiswa, *_aktif, *_cuti, *_mangkir, jumlah_do,
     * jumlah_mahasiswa_tidak_aktif, ipk_rata_rata, jumlah_tepat_waktu, _normal, _perhatian, _kritis,
     * eligible, tidak_eligible)
     */
    public function exportDashboard($filters = [])
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);
        $tahunMasuk = $filters['tahun_masuk'] ?? null;

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan Prodi');

        $headers = [
            'Tahun Masuk',
            'Jml Mahasiswa', 'Aktif', 'Cuti', 'Mangkir', 'DO', 'Tdk Aktif',
            'IPK Rata-rata',
            'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis',
            'Eligible', 'Tdk Eligible',
        ];
        $this->writeTitleBlock($sheet, 'RINGKASAN PROGRAM STUDI', $prodi->kode_prodi . ' - ' . $prodi->nama, $tahunMasuk ? 'Filter Tahun Masuk: ' . $tahunMasuk : 'Semua Tahun Angkatan', count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        foreach ($this->getRingkasanPerTahun($prodiId, $tahunMasuk) as $i => $row) {
            $sheet->setCellValue('A' . $startRow, $row['tahun_masuk']);
            $sheet->setCellValue('B' . $startRow, $row['jumlah_mahasiswa']);
            $sheet->setCellValue('C' . $startRow, $row['jumlah_mahasiswa_aktif']);
            $sheet->setCellValue('D' . $startRow, $row['jumlah_mahasiswa_cuti']);
            $sheet->setCellValue('E' . $startRow, $row['jumlah_mahasiswa_mangkir']);
            $sheet->setCellValue('F' . $startRow, $row['jumlah_do']);
            $sheet->setCellValue('G' . $startRow, $row['jumlah_mahasiswa_tidak_aktif']);
            $sheet->setCellValue('H' . $startRow, number_format((float) $row['ipk_rata_rata'], 2));
            $sheet->setCellValue('I' . $startRow, $row['jumlah_tepat_waktu']);
            $sheet->setCellValue('J' . $startRow, $row['jumlah_normal']);
            $sheet->setCellValue('K' . $startRow, $row['jumlah_perhatian']);
            $sheet->setCellValue('L' . $startRow, $row['jumlah_kritis']);
            $sheet->setCellValue('M' . $startRow, $row['eligible']);
            $sheet->setCellValue('N' . $startRow, $row['tidak_eligible']);
            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'Admin_Ringkasan_' . $prodi->kode_prodi . '_' . date('Y-m-d'));
    }

    /**
     * Export Dashboard Detail to XLSX (match `dashboard/detail.tahun_angkatan[]`)
     * 9 kolom: tahun_masuk, jumlah_mahasiswa, mahasiswa_aktif, jumlah_cuti_2x, ipk_rata_rata,
     * tepat_waktu, normal, perhatian, kritis (Admin display shape)
     */
    public function exportDashboardDetail($filters = [])
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);
        $tahunMasuk = $filters['tahun_masuk'] ?? null;

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Dashboard');

        $headers = ['Tahun Masuk', 'Jumlah Mhs', 'Aktif', 'Cuti 2x', 'IPK Rata', 'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis'];
        $this->writeTitleBlock($sheet, 'LAPORAN DETAIL DASHBOARD ADMIN', $prodi->kode_prodi . ' - ' . $prodi->nama, $tahunMasuk ? 'Filter Angkatan: ' . $tahunMasuk : 'Per Tahun Angkatan', count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        foreach ($this->getTahunData($prodiId, $tahunMasuk) as $i => $row) {
            $sheet->setCellValue('A' . $startRow, $row['tahun_masuk']);
            $sheet->setCellValue('B' . $startRow, $row['jumlah_mahasiswa']);
            $sheet->setCellValue('C' . $startRow, $row['mahasiswa_aktif']);
            $sheet->setCellValue('D' . $startRow, $row['jumlah_cuti_2x']);
            $sheet->setCellValue('E' . $startRow, number_format((float) $row['ipk_rata_rata'], 2));
            $sheet->setCellValue('F' . $startRow, $row['tepat_waktu']);
            $sheet->setCellValue('G' . $startRow, $row['normal']);
            $sheet->setCellValue('H' . $startRow, $row['perhatian']);
            $sheet->setCellValue('I' . $startRow, $row['kritis']);
            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'Admin_Dashboard_Detail_' . $prodi->kode_prodi . '_' . date('Y-m-d'));
    }

    /**
     * Per tahun aggregation, 14 metrik. Mirrors `AdminDashboardService::getTabelRingkasanProdiPerTahun()`.
     */
    private function getRingkasanPerTahun($prodiId, $tahunMasuk = null)
    {
        $query = AkademikMahasiswa::select(
            'akademik_mahasiswa.tahun_masuk',
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
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $tahunData = $query->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get()
            ->keyBy('tahun_masuk');

        $doData = AkademikMahasiswa::select(
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_do')
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
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa_tidak_aktif')
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
                'jumlah_mahasiswa_aktif' => $item->jumlah_mahasiswa_aktif,
                'jumlah_mahasiswa_cuti' => $item->jumlah_mahasiswa_cuti,
                'jumlah_mahasiswa_mangkir' => $item->jumlah_mahasiswa_mangkir,
                'jumlah_do' => $doData->get($item->tahun_masuk)->jumlah_do ?? 0,
                'jumlah_mahasiswa_tidak_aktif' => $tidakAktifData->get($item->tahun_masuk)->jumlah_mahasiswa_tidak_aktif ?? 0,
                'ipk_rata_rata' => $item->ipk_rata_rata,
                'jumlah_tepat_waktu' => $item->jumlah_tepat_waktu,
                'jumlah_normal' => $item->jumlah_normal,
                'jumlah_perhatian' => $item->jumlah_perhatian,
                'jumlah_kritis' => $item->jumlah_kritis,
                'eligible' => $item->eligible ?? 0,
                'tidak_eligible' => $item->tidak_eligible ?? 0,
            ];
        })->values()->toArray();
    }

    /**
     * Mirrors `AdminDashboardService::getDetailDashboard()` — 9 kolom dengan cuti_2x.
     */
    private function getTahunData($prodiId, $tahunMasuk = null)
    {
        $query = AkademikMahasiswa::select(
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as mahasiswa_aktif'),
            DB::raw('SUM(CASE WHEN mahasiswa.cuti_2 = "yes" THEN 1 ELSE 0 END) as jumlah_cuti_2x'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as tepat_waktu'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as normal'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as perhatian'),
            DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as kritis')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do", "tidak_aktif")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        return $query->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'tahun_masuk' => $item->tahun_masuk,
                    'jumlah_mahasiswa' => $item->jumlah_mahasiswa,
                    'mahasiswa_aktif' => $item->mahasiswa_aktif,
                    'jumlah_cuti_2x' => $item->jumlah_cuti_2x,
                    'ipk_rata_rata' => $item->ipk_rata_rata,
                    'tepat_waktu' => $item->tepat_waktu,
                    'normal' => $item->normal,
                    'perhatian' => $item->perhatian,
                    'kritis' => $item->kritis,
                ];
            })->values()->toArray();
    }
}
