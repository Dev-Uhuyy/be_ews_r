<?php

namespace App\Services\Kaprodi\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StatistikKelulusanExportService
{
    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    /**
     * Export Statistik Kelulusan to XLSX
     *
     * @param array $filters Optional: tahun_masuk (filter per tahun angkatan)
     */
    public function exportStatistikKelulusan($filters = [])
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);
        $tahunMasuk = $filters['tahun_masuk'] ?? null;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN STATISTIK KELULUSAN');
        $sheet->setCellValue('A2', $prodi->kode_prodi . ' - ' . $prodi->nama);
        if ($tahunMasuk) {
            $sheet->setCellValue('A3', 'Filter Tahun Masuk: ' . $tahunMasuk);
        } else {
            $sheet->setCellValue('A3', 'Per Tahun Angkatan');
        }
        $sheet->setCellValue('A4', 'Dicetak: ' . date('d-m-Y H:i'));

        $startRow = 6;

        // Summary
        $stats = $this->getStatistikPerProdi($prodiId, $tahunMasuk);
        $sheet->setCellValue('A' . $startRow, 'Total Mahasiswa: ' . $stats['jumlah_mahasiswa']);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'IPK < 2: ' . $stats['ipk_dibawah_2'] . ' | SKS < 144: ' . $stats['sks_kurang_dari_144']);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Nilai D > 5%: ' . $stats['nilai_d_lebih_dari_5_persen'] . ' | Ada Nilai E: ' . $stats['ada_nilai_e']);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Eligible: ' . $stats['eligible'] . ' | Tidak Eligible: ' . $stats['tidak_eligible'] . ' | IPK Rata: ' . $stats['ipk_rata_rata']);
        $startRow += 2;

        // Detail Per Tahun
        $detailPerTahun = $this->getStatistikPerTahun($prodiId, $tahunMasuk);

        $headers = ['Tahun Masuk', 'Jumlah Mhs', 'IPK < 2', 'SKS < 144', 'Nilai D > 5%', 'Ada Nilai E', 'Eligible', 'Tidak Eligible', 'IPK Rata'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($headers));

        $startRow++;
        foreach ($detailPerTahun as $row) {
            $sheet->setCellValue('A' . $startRow, $row->tahun_masuk);
            $sheet->setCellValue('B' . $startRow, $row->jumlah_mahasiswa);
            $sheet->setCellValue('C' . $startRow, $row->ipk_dibawah_2);
            $sheet->setCellValue('D' . $startRow, $row->sks_kurang_dari_144);
            $sheet->setCellValue('E' . $startRow, $row->nilai_d_lebih_dari_5_persen);
            $sheet->setCellValue('F' . $startRow, $row->ada_nilai_e);
            $sheet->setCellValue('G' . $startRow, $row->eligible);
            $sheet->setCellValue('H' . $startRow, $row->tidak_eligible);
            $sheet->setCellValue('I' . $startRow, $row->ipk_rata_rata);
            $startRow++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->saveFile($spreadsheet, 'Kaprodi_Statistik_Kelulusan_' . $prodi->kode_prodi . '_' . date('Y-m-d'));
    }

    private function getStatistikPerProdi($prodiId, $tahunMasuk = null)
    {
        $prodi = Prodi::find($prodiId);

        $query = AkademikMahasiswa::select(
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_dibawah_2'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_lebih_dari_5_persen'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as ada_nilai_e'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $stats = $query->first();

        return [
            'prodi' => ['kode_prodi' => $prodi->kode_prodi, 'nama_prodi' => $prodi->nama],
            'jumlah_mahasiswa' => $stats->jumlah_mahasiswa ?? 0,
            'ipk_dibawah_2' => $stats->ipk_dibawah_2 ?? 0,
            'sks_kurang_dari_144' => $stats->sks_kurang_dari_144 ?? 0,
            'nilai_d_lebih_dari_5_persen' => $stats->nilai_d_lebih_dari_5_persen ?? 0,
            'ada_nilai_e' => $stats->ada_nilai_e ?? 0,
            'eligible' => $stats->eligible ?? 0,
            'tidak_eligible' => $stats->tidak_eligible ?? 0,
            'ipk_rata_rata' => $stats->ipk_rata_rata ?? 0,
        ];
    }

    private function getStatistikPerTahun($prodiId, $tahunMasuk = null)
    {
        $query = AkademikMahasiswa::select(
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.ipk < 2 THEN 1 ELSE 0 END) as ipk_dibawah_2'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.sks_lulus < 144 THEN 1 ELSE 0 END) as sks_kurang_dari_144'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as nilai_d_lebih_dari_5_persen'),
                    DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as ada_nilai_e'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "eligible" THEN 1 ELSE 0 END) as eligible'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status_kelulusan = "noneligible" THEN 1 ELSE 0 END) as tidak_eligible'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        return $query->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get();
    }

    private function styleHeader($sheet, $row, $colCount)
    {
        $styleArray = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCCCCC']],
        ];
        $endCol = chr(64 + $colCount);
        $sheet->getStyle('A' . $row . ':' . $endCol . $row)->applyFromArray($styleArray);
    }

    private function saveFile($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);
        $filename = $filename . '.xlsx';
        $tempPath = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $writer->save($tempPath);

        response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->send();
    }
}
