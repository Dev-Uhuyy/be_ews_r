<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MahasiswaListExportService
{
    /**
     * Export Mahasiswa List to XLSX
     */
    public function exportMahasiswaList($filters = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $filterDesc = $this->buildFilterDescription($filters);
        $sheet->setCellValue('A1', 'LAPORAN LIST MAHASISWA');
        $sheet->setCellValue('A2', $filterDesc);
        $sheet->setCellValue('A3', 'Dicetak: ' . date('d-m-Y H:i'));

        $startRow = 5;

        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Prodi', 'Tahun Masuk', 'SKS Total', 'IPK', 'Nilai D', 'Nilai E', 'Status EWS', 'Status Kelulusan'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($headers));

        $startRow++;
        $no = 1;

        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'prodis.nama as nama_prodi',
                    'prodis.kode_prodi',
                    'akademik_mahasiswa.tahun_masuk',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.ipk',
                    'akademik_mahasiswa.nilai_d_melebihi_batas',
                    'akademik_mahasiswa.nilai_e',
                    'early_warning_system.status as ews_status',
                    'early_warning_system.status_kelulusan'
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        // Apply filters
        if (!empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        }
        if (!empty($filters['ipk_max']) && is_numeric($filters['ipk_max'])) {
            $query->where('akademik_mahasiswa.ipk', '<', $filters['ipk_max']);
        }
        if (!empty($filters['sks_max']) && is_numeric($filters['sks_max'])) {
            $query->where('akademik_mahasiswa.sks_lulus', '<', $filters['sks_max']);
        }
        if (!empty($filters['has_nilai_d'])) {
            $hasNilaiD = filter_var($filters['has_nilai_d'], FILTER_VALIDATE_BOOLEAN);
            $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', $hasNilaiD ? 'yes' : 'no');
        }
        if (!empty($filters['has_nilai_e'])) {
            $hasNilaiE = filter_var($filters['has_nilai_e'], FILTER_VALIDATE_BOOLEAN);
            $query->where('akademik_mahasiswa.nilai_e', $hasNilaiE ? 'yes' : 'no');
        }
        if (!empty($filters['status_kelulusan'])) {
            $query->where('early_warning_system.status_kelulusan', $filters['status_kelulusan']);
        }
        if (!empty($filters['ews_status'])) {
            $query->where('early_warning_system.status', $filters['ews_status']);
        }

        $mahasiswas = $query->orderBy('prodis.nama', 'asc')
            ->orderBy('users.name', 'asc')
            ->get();

        foreach ($mahasiswas as $mhs) {
            $sheet->setCellValue('A' . $startRow, $no++);
            $sheet->setCellValue('B' . $startRow, $mhs->nim);
            $sheet->setCellValue('C' . $startRow, $mhs->nama_mahasiswa);
            $sheet->setCellValue('D' . $startRow, $mhs->kode_prodi . ' - ' . $mhs->nama_prodi);
            $sheet->setCellValue('E' . $startRow, $mhs->tahun_masuk);
            $sheet->setCellValue('F' . $startRow, $mhs->sks_lulus ?? 0);
            $sheet->setCellValue('G' . $startRow, $mhs->ipk ?? 0);
            $sheet->setCellValue('H' . $startRow, $mhs->nilai_d_melebihi_batas ?? 'no');
            $sheet->setCellValue('I' . $startRow, $mhs->nilai_e ?? 'no');
            $sheet->setCellValue('J' . $startRow, $mhs->ews_status ?? '-');
            $sheet->setCellValue('K' . $startRow, $mhs->status_kelulusan ?? '-');

            $startRow++;
        }

        // Auto size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->saveFile($spreadsheet, 'Dekan_Mahasiswa_List_' . date('Y-m-d'));
    }

    private function buildFilterDescription($filters)
    {
        $desc = [];
        if (!empty($filters['prodi_id'])) {
            $prodi = \App\Models\Prodi::find($filters['prodi_id']);
            $desc[] = 'Prodi: ' . ($prodi ? $prodi->nama : $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $desc[] = 'Tahun Masuk: ' . $filters['tahun_masuk'];
        }
        if (!empty($filters['ipk_max'])) {
            $desc[] = 'IPK < ' . $filters['ipk_max'];
        }
        if (!empty($filters['sks_max'])) {
            $desc[] = 'SKS < ' . $filters['sks_max'];
        }
        if (!empty($filters['has_nilai_d'])) {
            $desc[] = 'Ada Nilai D';
        }
        if (!empty($filters['has_nilai_e'])) {
            $desc[] = 'Ada Nilai E';
        }
        if (!empty($filters['status_kelulusan'])) {
            $desc[] = 'Status Kelulusan: ' . $filters['status_kelulusan'];
        }
        if (!empty($filters['ews_status'])) {
            $desc[] = 'Status EWS: ' . $filters['ews_status'];
        }

        return empty($desc) ? 'Semua Filter' : implode(' | ', $desc);
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
