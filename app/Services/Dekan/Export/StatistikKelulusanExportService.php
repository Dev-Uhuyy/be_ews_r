<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StatistikKelulusanExportService
{
    use ExportFormatterTrait;

    public function exportStatistikKelulusan($filters = [])
    {
        $prodiId = $filters['prodi_id'] ?? null;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Statistik Kelulusan');

        $prodis = $prodiId ? Prodi::where('id', $prodiId)->get() : Prodi::orderBy('kode_prodi')->get();
        $prodiNama = $prodiId ? ($prodis->first()->nama ?? 'Prodi') : 'Semua Prodi';

        $headers = ['Unit / Tahun Masuk', 'Jumlah Mhs', 'IPK < 2', 'SKS < 144', 'Nilai D > 5%', 'Ada Nilai E', 'Eligible', 'Tidak Eligible', 'IPK Rata'];

        $this->writeTitleBlock($sheet, 'LAPORAN STATISTIK KELULUSAN', 'Analisis Kriteria Kelulusan', 'Prodi: ' . $prodiNama, count($headers));

        $startRow = 6;

        /* ── SECTION 1: RINGKASAN PER PROGRAM STUDI ── */
        if (!$prodiId || $prodis->count() > 1) {
            $this->writeSectionHeader($sheet, $startRow, 'RINGKASAN PER PROGRAM STUDI', count($headers));
            $startRow++;

            $this->writeHeaderRow($sheet, $startRow, $headers);
            // NB: writeHeaderRow will freeze pane at this row. This is what we want.
            $startRow++;

            foreach ($prodis as $i => $prodi) {
                $stats = $this->getStatistikPerProdi($prodi->id);
                $sheet->setCellValue('A' . $startRow, $prodi->kode_prodi . ' - ' . $prodi->nama);
                $sheet->setCellValue('B' . $startRow, $stats['jumlah_mahasiswa']);
                $sheet->setCellValue('C' . $startRow, $stats['ipk_dibawah_2']);
                $sheet->setCellValue('D' . $startRow, $stats['sks_kurang_dari_144']);
                $sheet->setCellValue('E' . $startRow, $stats['nilai_d_lebih_dari_5_persen']);
                $sheet->setCellValue('F' . $startRow, $stats['ada_nilai_e']);
                $sheet->setCellValue('G' . $startRow, $stats['eligible']);
                $sheet->setCellValue('H' . $startRow, $stats['tidak_eligible']);
                $sheet->setCellValue('I' . $startRow, number_format((float)$stats['ipk_rata_rata'], 2));

                $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
                $sheet->getStyle('A' . $startRow)->getFont()->setBold(true);
                $startRow++;
            }
            $startRow += 2;
        }

        /* ── SECTION 2: DETAIL PER ANGKATAN ── */
        $this->writeSectionHeader($sheet, $startRow, 'DETAIL ANALISIS PER ANGKATAN', count($headers));
        $startRow++;

        foreach ($prodis as $prodi) {
            // Sub-header for prodi detail
            $sheet->setCellValue('A' . $startRow, 'PRODI: ' . $prodi->kode_prodi . ' - ' . $prodi->nama);
            $sheet->getStyle('A' . $startRow)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A' . $startRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F0F4F8');
            $sheet->mergeCells('A' . $startRow . ':' . $this->colLetter(count($headers)) . $startRow);
            $startRow++;

            // Data headers for Detail (we don't use writeHeaderRow here to avoid overriding freezePane)
            foreach ($headers as $col => $label) {
                $sheet->setCellValueByColumnAndRow($col + 1, $startRow, ($col === 0 ? 'Tahun Masuk' : $label));
            }
            $endColLetter = $this->colLetter(count($headers));
            $sheet->getStyle("A{$startRow}:{$endColLetter}{$startRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '3D8BCD']],
            ]);
            $startRow++;

            $detailPerTahun = $this->getStatistikPerTahun($prodi->id);
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
            $startRow += 2;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Dekan_Statistik_Kelulusan_' . date('Y-m-d'));
    }

    private function getStatistikPerProdi($prodiId)
    {
        return AkademikMahasiswa::select(
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
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->first()->toArray();
    }

    private function getStatistikPerTahun($prodiId)
    {
        return AkademikMahasiswa::select(
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
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
                ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
                ->get();
    }
}
