<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DashboardExportService
{
    use ExportFormatterTrait;

    public function exportDashboard()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan Prodi');

        $headers = ['Kode Prodi', 'Nama Prodi', 'Jmlh Mhs', 'Aktif', 'Cuti', 'Mangkir', 'IPK Rata-rata', 'Tepat Waktu', 'Perhatian', 'Kritis'];
        $this->writeTitleBlock($sheet, 'TABLE RINGKASAN MAHASISWA', 'Semua Program Studi', 'Semua Tahun Angkatan', count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $rows = $this->getRingkasanPerProdi();
        foreach ($rows as $i => $row) {
            $sheet->setCellValue('A' . $startRow, $row->kode_prodi);
            $sheet->setCellValue('B' . $startRow, $row->nama_prodi);
            $sheet->setCellValue('C' . $startRow, $row->jumlah_mahasiswa);
            $sheet->setCellValue('D' . $startRow, $row->jumlah_aktif);
            $sheet->setCellValue('E' . $startRow, $row->jumlah_cuti);
            $sheet->setCellValue('F' . $startRow, $row->jumlah_mangkir);
            $sheet->setCellValue('G' . $startRow, number_format((float)$row->ipk_rata_rata, 2));
            $sheet->setCellValue('H' . $startRow, $row->jumlah_tepat_waktu);
            $sheet->setCellValue('I' . $startRow, $row->jumlah_perhatian);
            $sheet->setCellValue('J' . $startRow, $row->jumlah_kritis);
            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Dekan_Ringkasan_Prodi_' . date('Y-m-d'));
    }

    public function exportDashboardDetail($filters = [])
    {
        $prodiId   = $filters['prodi_id'] ?? null;
        $prodiNama = $prodiId ? (Prodi::find($prodiId)?->nama ?? '?') : 'Semua Prodi';

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail per Angkatan');

        $headers = ['Tahun Masuk', 'Jumlah Mhs', 'Aktif', 'Cuti', 'Mangkir', 'IPK Rata', 'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis'];
        $this->writeTitleBlock($sheet, 'DETAIL DASHBOARD DEKAN', 'Program Studi: ' . $prodiNama, 'Breakdown per Tahun Angkatan', count($headers));

        $prodis   = $prodiId ? Prodi::where('id', $prodiId)->get() : Prodi::orderBy('kode_prodi')->get();
        $startRow = 6;

        foreach ($prodis as $prodi) {
            $tahunData = $this->getDataPerTahunForProdi($prodi->id);
            if ($tahunData->isEmpty()) continue;

            $this->writeSectionHeader($sheet, $startRow, $prodi->kode_prodi . '  –  ' . $prodi->nama, count($headers));
            $startRow++;

            $this->writeHeaderRow($sheet, $startRow, $headers);
            $startRow++;

            foreach ($tahunData as $i => $row) {
                $sheet->setCellValue('A' . $startRow, $row->tahun_masuk);
                $sheet->setCellValue('B' . $startRow, $row->jumlah_mahasiswa);
                $sheet->setCellValue('C' . $startRow, $row->mahasiswa_aktif);
                $sheet->setCellValue('D' . $startRow, $row->jumlah_cuti);
                $sheet->setCellValue('E' . $startRow, $row->jumlah_mangkir);
                $sheet->setCellValue('F' . $startRow, number_format((float)$row->ipk_rata_rata, 2));
                $sheet->setCellValue('G' . $startRow, $row->tepat_waktu);
                $sheet->setCellValue('H' . $startRow, $row->normal);
                $sheet->setCellValue('I' . $startRow, $row->perhatian);
                $sheet->setCellValue('J' . $startRow, $row->kritis);
                $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
                $startRow++;
            }
            $startRow += 2;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Dekan_Dashboard_Detail_' . date('Y-m-d'));
    }

    // ── Queries ──────────────────────────────────────────────────────────────

    private function getRingkasanPerProdi()
    {
        return AkademikMahasiswa::select(
                    'prodis.kode_prodi',
                    'prodis.nama as nama_prodi',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="aktif"   THEN 1 ELSE 0 END) as jumlah_aktif'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="cuti"    THEN 1 ELSE 0 END) as jumlah_cuti'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="mangkir" THEN 1 ELSE 0 END) as jumlah_mangkir'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk),2) as ipk_rata_rata'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status="tepat_waktu" THEN 1 ELSE 0 END) as jumlah_tepat_waktu'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status="perhatian"   THEN 1 ELSE 0 END) as jumlah_perhatian'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status="kritis"      THEN 1 ELSE 0 END) as jumlah_kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus","do")')
                ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama')
                ->orderBy('prodis.kode_prodi')
                ->get();
    }

    private function getDataPerTahunForProdi(int $prodiId)
    {
        return AkademikMahasiswa::select(
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="aktif"   THEN 1 ELSE 0 END) as mahasiswa_aktif'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="cuti"    THEN 1 ELSE 0 END) as jumlah_cuti'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="mangkir" THEN 1 ELSE 0 END) as jumlah_mangkir'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk),2) as ipk_rata_rata'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status="tepat_waktu" THEN 1 ELSE 0 END) as tepat_waktu'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status="normal"      THEN 1 ELSE 0 END) as normal'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status="perhatian"   THEN 1 ELSE 0 END) as perhatian'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status="kritis"      THEN 1 ELSE 0 END) as kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus","do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
                ->get();
    }
}
