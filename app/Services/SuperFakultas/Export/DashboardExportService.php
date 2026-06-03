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

    public function exportDashboard()
    {
        $spreadsheet = new Spreadsheet;

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan Prodi');
        $headers = ['Kode Prodi', 'Nama Prodi', 'Jmlh Mhs', 'Aktif', 'Cuti', 'Mangkir', 'DO', 'Meninggal', 'IPK Rata-rata', 'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis'];
        $this->writeTitleBlock($sheet1, 'TABLE RINGKASAN MAHASISWA', 'Semua Program Studi', 'Semua Tahun Angkatan', count($headers));
        $startRow = 6;
        $this->writeHeaderRow($sheet1, $startRow, $headers);
        $startRow++;
        $sheet1->freezePane('A' . $startRow);
        $rows = $this->getRingkasanPerProdi();
        foreach ($rows as $i => $row) {
            $sheet1->setCellValue('A'.$startRow, $row->kode_prodi);
            $sheet1->setCellValue('B'.$startRow, $row->nama_prodi);
            $sheet1->setCellValue('C'.$startRow, $row->jumlah_mahasiswa);
            $sheet1->setCellValue('D'.$startRow, $row->jumlah_aktif);
            $sheet1->setCellValue('E'.$startRow, $row->jumlah_cuti);
            $sheet1->setCellValue('F'.$startRow, $row->jumlah_mangkir);
            $sheet1->setCellValue('G'.$startRow, $row->jumlah_do);
            $sheet1->setCellValue('H'.$startRow, $row->jumlah_meninggal);
            $sheet1->setCellValue('I'.$startRow, number_format((float) $row->ipk_rata_rata, 2));
            $sheet1->setCellValue('J'.$startRow, $row->jumlah_tepat_waktu);
            $sheet1->setCellValue('K'.$startRow, $row->jumlah_normal);
            $sheet1->setCellValue('L'.$startRow, $row->jumlah_perhatian);
            $sheet1->setCellValue('M'.$startRow, $row->jumlah_kritis);
            $this->styleDataRow($sheet1, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }
        $this->autoSizeColumns($sheet1, count($headers));

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Detail per Angkatan');
        $headers2 = ['Kode Prodi', 'Nama Prodi', 'Tahun Masuk', 'Jumlah Mhs', 'Aktif', 'Cuti', 'Mangkir', 'DO', 'Meninggal', 'IPK Rata', 'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis'];
        $this->writeTitleBlock($sheet2, 'DETAIL DASHBOARD SUPER FAKULTAS', 'Semua Program Studi', 'Breakdown per Tahun Angkatan', count($headers2));
        $startRow2 = 6;

        $prodis = Prodi::orderBy('kode_prodi')->get();
        foreach ($prodis as $prodi) {
            $tahunData = $this->getDataPerTahunForProdi($prodi->id);
            if ($tahunData->isEmpty()) {
                continue;
            }
            $this->writeSectionHeader($sheet2, $startRow2, $prodi->kode_prodi.'  –  '.$prodi->nama, count($headers2));
            $startRow2++;

            $this->writeHeaderRowNoFreeze($sheet2, $startRow2, $headers2);
            $startRow2++;

            foreach ($tahunData as $i => $row) {
                $sheet2->setCellValue('A'.$startRow2, $prodi->kode_prodi);
                $sheet2->setCellValue('B'.$startRow2, $prodi->nama);
                $sheet2->setCellValue('C'.$startRow2, $row->tahun_masuk);
                $sheet2->setCellValue('D'.$startRow2, $row->jumlah_mahasiswa);
                $sheet2->setCellValue('E'.$startRow2, $row->mahasiswa_aktif);
                $sheet2->setCellValue('F'.$startRow2, $row->jumlah_cuti);
                $sheet2->setCellValue('G'.$startRow2, $row->jumlah_mangkir);
                $sheet2->setCellValue('H'.$startRow2, $row->jumlah_do);
                $sheet2->setCellValue('I'.$startRow2, $row->jumlah_meninggal);
                $sheet2->setCellValue('J'.$startRow2, number_format((float) $row->ipk_rata_rata, 2));
                $sheet2->setCellValue('K'.$startRow2, $row->tepat_waktu);
                $sheet2->setCellValue('L'.$startRow2, $row->normal);
                $sheet2->setCellValue('M'.$startRow2, $row->perhatian);
                $sheet2->setCellValue('N'.$startRow2, $row->kritis);
                $this->styleDataRow($sheet2, $startRow2, count($headers2), $i % 2 === 1);
                $startRow2++;
            }
            $startRow2++;
        }
        $this->autoSizeColumns($sheet2, count($headers2));

        $this->saveFile($spreadsheet, 'SuperFakultas_Ringkasan_Prodi_'.date('Y-m-d'));
    }

    public function exportDashboardDetail($filters = [])
    {
        $prodiId = $filters['prodi_id'] ?? null;
        $prodiNama = $prodiId ? (Prodi::find($prodiId)?->nama ?? '?') : 'Semua Prodi';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail per Angkatan');

        $headers = ['Tahun Masuk', 'Jumlah Mhs', 'Aktif', 'Cuti', 'Mangkir', 'DO', 'Meninggal', 'IPK Rata', 'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis'];
        $this->writeTitleBlock($sheet, 'DETAIL DASHBOARD SUPER FAKULTAS', 'Program Studi: '.$prodiNama, 'Breakdown per Tahun Angkatan', count($headers));

        $prodis = $prodiId ? Prodi::where('id', $prodiId)->get() : Prodi::orderBy('kode_prodi')->get();
        $startRow = 6;

        foreach ($prodis as $prodi) {
            $tahunData = $this->getDataPerTahunForProdi($prodi->id);
            if ($tahunData->isEmpty()) {
                continue;
            }

            $this->writeSectionHeader($sheet, $startRow, $prodi->kode_prodi.'  –  '.$prodi->nama, count($headers));
            $startRow++;

            $this->writeHeaderRowNoFreeze($sheet, $startRow, $headers);
            $startRow++;

            foreach ($tahunData as $i => $row) {
                $sheet->setCellValue('A'.$startRow, $row->tahun_masuk);
                $sheet->setCellValue('B'.$startRow, $row->jumlah_mahasiswa);
                $sheet->setCellValue('C'.$startRow, $row->mahasiswa_aktif);
                $sheet->setCellValue('D'.$startRow, $row->jumlah_cuti);
                $sheet->setCellValue('E'.$startRow, $row->jumlah_mangkir);
                $sheet->setCellValue('F'.$startRow, $row->jumlah_do);
                $sheet->setCellValue('G'.$startRow, $row->jumlah_meninggal);
                $sheet->setCellValue('H'.$startRow, number_format((float) $row->ipk_rata_rata, 2));
                $sheet->setCellValue('I'.$startRow, $row->tepat_waktu);
                $sheet->setCellValue('J'.$startRow, $row->normal);
                $sheet->setCellValue('K'.$startRow, $row->perhatian);
                $sheet->setCellValue('L'.$startRow, $row->kritis);
                $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
                $startRow++;
            }
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'SuperFakultas_Dashboard_Detail_'.date('Y-m-d'));
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
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="do"      THEN 1 ELSE 0 END) as jumlah_do'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="meninggal" THEN 1 ELSE 0 END) as jumlah_meninggal'),
            DB::raw('ROUND(AVG(akademik_mahasiswa.ipk),2) as ipk_rata_rata'),
            DB::raw('SUM(CASE WHEN early_warning_system.status="tepat_waktu" THEN 1 ELSE 0 END) as jumlah_tepat_waktu'),
            DB::raw('SUM(CASE WHEN early_warning_system.status="normal"      THEN 1 ELSE 0 END) as jumlah_normal'),
            DB::raw('SUM(CASE WHEN early_warning_system.status="perhatian"   THEN 1 ELSE 0 END) as jumlah_perhatian'),
            DB::raw('SUM(CASE WHEN early_warning_system.status="kritis"      THEN 1 ELSE 0 END) as jumlah_kritis')
        )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->groupBy('prodis.id', 'prodis.kode_prodi', 'prodis.nama')
            ->orderBy('prodis.kode_prodi')
            ->get();
    }

    private function getDataPerTahunForProdi(int $prodiId)
    {
        return AkademikMahasiswa::select(
            'akademik_mahasiswa.tahun_masuk',
            DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="aktif"     THEN 1 ELSE 0 END) as mahasiswa_aktif'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="cuti"      THEN 1 ELSE 0 END) as jumlah_cuti'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="mangkir"   THEN 1 ELSE 0 END) as jumlah_mangkir'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="do"       THEN 1 ELSE 0 END) as jumlah_do'),
            DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa)="meninggal" THEN 1 ELSE 0 END) as jumlah_meninggal'),
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
            ->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get();
    }
}
