<?php

namespace App\Services\Kaprodi\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StatistikKelulusanExportService
{
    use ExportFormatterTrait;

    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    public function exportStatistikKelulusan($filters = [])
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);
        $tahunMasuk = $filters['tahun_masuk'] ?? null;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Statistik Kelulusan');

        $headers = ['Tahun Masuk', 'Jumlah Mhs', 'IPK < 2', 'SKS < 144', 'Nilai D > 5%', 'Ada Nilai E', 'Eligible', 'Tidak Eligible', 'IPK Rata'];
        $this->writeTitleBlock($sheet, 'LAPORAN STATISTIK KELULUSAN', $prodi->kode_prodi . ' - ' . $prodi->nama, $tahunMasuk ? 'Angkatan: ' . $tahunMasuk : 'Semua Angkatan', count($headers));

        $startRow = 6;
        $this->writeSectionHeader($sheet, $startRow, 'RINGKASAN STATISTIK', count($headers));
        $startRow++;

        $stats = $this->getStatistikPerProdi($prodiId, $tahunMasuk);
        $sheet->setCellValue('A' . $startRow, 'TOTAL');
        $sheet->setCellValue('B' . $startRow, $stats['jumlah_mahasiswa']);
        $sheet->setCellValue('C' . $startRow, $stats['ipk_dibawah_2']);
        $sheet->setCellValue('D' . $startRow, $stats['sks_kurang_dari_144']);
        $sheet->setCellValue('E' . $startRow, $stats['nilai_d_lebih_dari_5_persen']);
        $sheet->setCellValue('F' . $startRow, $stats['ada_nilai_e']);
        $sheet->setCellValue('G' . $startRow, $stats['eligible']);
        $sheet->setCellValue('H' . $startRow, $stats['tidak_eligible']);
        $sheet->setCellValue('I' . $startRow, number_format((float)$stats['ipk_rata_rata'], 2));
        $sheet->getStyle('A' . $startRow . ':I' . $startRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $startRow . ':I' . $startRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $startRow += 2;

        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $detailPerTahun = $this->getStatistikPerTahun($prodiId, $tahunMasuk);
        foreach ($detailPerTahun as $i => $row) {
            $sheet->setCellValue('A' . $startRow, $row->tahun_masuk);
            $sheet->setCellValue('B' . $startRow, $row->jumlah_mahasiswa);
            $sheet->setCellValue('C' . $startRow, $row->ipk_dibawah_2);
            $sheet->setCellValue('D' . $startRow, $row->sks_kurang_dari_144);
            $sheet->setCellValue('E' . $startRow, $row->nilai_d_lebih_dari_5_persen);
            $sheet->setCellValue('F' . $startRow, $row->ada_nilai_e);
            $sheet->setCellValue('G' . $startRow, $row->eligible);
            $sheet->setCellValue('H' . $startRow, $row->tidak_eligible);
            $sheet->setCellValue('I' . $startRow, number_format((float)$row->ipk_rata_rata, 2));
            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Kaprodi_Statistik_Kelulusan_' . $prodi->kode_prodi . '_' . date('Y-m-d'));
    }

    private function getStatistikPerProdi($prodiId, $tahunMasuk = null)
    {
        $q = AkademikMahasiswa::select(
                DB::raw('COUNT(*) as jumlah_mahasiswa'),
                DB::raw('SUM(CASE WHEN ipk < 2 THEN 1 ELSE 0 END) as ipk_dibawah_2'),
                DB::raw('SUM(CASE WHEN sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
                DB::raw('SUM(CASE WHEN nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_lebih_dari_5_persen'),
                DB::raw('SUM(CASE WHEN nilai_e = "yes" THEN 1 ELSE 0 END) as ada_nilai_e'),
                DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible'),
                DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata')
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        if ($tahunMasuk) $q->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        return (array)$q->first()->toArray();
    }

    private function getStatistikPerTahun($prodiId, $tahunMasuk = null)
    {
        $q = AkademikMahasiswa::select(
                'akademik_mahasiswa.tahun_masuk',
                DB::raw('COUNT(*) as jumlah_mahasiswa'),
                DB::raw('SUM(CASE WHEN ipk < 2 THEN 1 ELSE 0 END) as ipk_dibawah_2'),
                DB::raw('SUM(CASE WHEN sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
                DB::raw('SUM(CASE WHEN nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_lebih_dari_5_persen'),
                DB::raw('SUM(CASE WHEN nilai_e = "yes" THEN 1 ELSE 0 END) as ada_nilai_e'),
                DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible'),
                DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata')
            )
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('akademik_mahasiswa.tahun_masuk')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        if ($tahunMasuk) $q->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        return $q->groupBy('akademik_mahasiswa.tahun_masuk')->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')->get();
    }
}
