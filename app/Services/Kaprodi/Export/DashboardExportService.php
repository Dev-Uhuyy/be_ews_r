<?php

namespace App\Services\Kaprodi\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DashboardExportService
{
    use ExportFormatterTrait;

    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    /**
     * Export Kaprodi Dashboard — Tabel Ringkasan Prodi per Tahun Masuk
     */
    public function exportDashboard($filters = [])
    {
        $prodiId    = $this->getProdiId();
        $prodi      = Prodi::find($prodiId);
        $tahunMasuk = $filters['tahun_masuk'] ?? null;

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan Prodi');

        $headers = ['Tahun Masuk', 'Jml Mahasiswa', 'Aktif', 'Cuti', 'Mangkir', 'IPK Rata-rata', 'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis'];
        $this->writeTitleBlock($sheet, 'RINGKASAN PROGRAM STUDI', $prodi->kode_prodi . ' - ' . $prodi->nama, $tahunMasuk ? 'Filter Tahun Masuk: ' . $tahunMasuk : 'Semua Tahun Angkatan', count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $rows = $this->getRingkasanPerTahun($prodiId, $tahunMasuk);
        foreach ($rows as $i => $row) {
            $sheet->setCellValue('A' . $startRow, $row->tahun_masuk);
            $sheet->setCellValue('B' . $startRow, $row->jumlah_mahasiswa);
            $sheet->setCellValue('C' . $startRow, $row->jumlah_mahasiswa_aktif);
            $sheet->setCellValue('D' . $startRow, $row->jumlah_mahasiswa_cuti);
            $sheet->setCellValue('E' . $startRow, $row->jumlah_mahasiswa_mangkir);
            $sheet->setCellValue('F' . $startRow, number_format((float)$row->ipk_rata_rata, 2));
            $sheet->setCellValue('G' . $startRow, $row->jumlah_tepat_waktu);
            $sheet->setCellValue('H' . $startRow, $row->jumlah_normal);
            $sheet->setCellValue('I' . $startRow, $row->jumlah_perhatian);
            $sheet->setCellValue('J' . $startRow, $row->jumlah_kritis);
            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Kaprodi_Ringkasan_' . $prodi->kode_prodi . '_' . date('Y-m-d'));
    }

    /**
     * Export Dashboard Detail to XLSX
     */
    public function exportDashboardDetail($filters = [])
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);
        $tahunMasuk = $filters['tahun_masuk'] ?? null;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Dashboard');

        $headers = ['Tahun Masuk', 'Jumlah Mhs', 'Aktif', 'Cuti 2x', 'IPK Rata', 'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis'];
        $this->writeTitleBlock($sheet, 'LAPORAN DETAIL DASHBOARD KAPRODI', $prodi->kode_prodi . ' - ' . $prodi->nama, $tahunMasuk ? 'Filter Angkatan: ' . $tahunMasuk : 'Per Tahun Angkatan', count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $tahunData = $this->getTahunData($prodiId, $tahunMasuk);
        foreach ($tahunData as $i => $row) {
            $sheet->setCellValue('A' . $startRow, $row->tahun_masuk);
            $sheet->setCellValue('B' . $startRow, $row->jumlah_mahasiswa);
            $sheet->setCellValue('C' . $startRow, $row->mahasiswa_aktif);
            $sheet->setCellValue('D' . $startRow, $row->jumlah_cuti_2x);
            $sheet->setCellValue('E' . $startRow, number_format((float)$row->ipk_rata_rata, 2));
            $sheet->setCellValue('F' . $startRow, $row->tepat_waktu);
            $sheet->setCellValue('G' . $startRow, $row->normal);
            $sheet->setCellValue('H' . $startRow, $row->perhatian);
            $sheet->setCellValue('I' . $startRow, $row->kritis);
            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Kaprodi_Dashboard_Detail_' . $prodi->kode_prodi . '_' . date('Y-m-d'));
    }

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
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis"      THEN 1 ELSE 0 END) as jumlah_kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);

        return $query->groupBy('akademik_mahasiswa.tahun_masuk')->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')->get();
    }

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
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);

        return $query->groupBy('akademik_mahasiswa.tahun_masuk')->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')->get();
    }
}
