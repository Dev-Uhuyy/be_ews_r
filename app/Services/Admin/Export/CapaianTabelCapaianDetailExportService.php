<?php

namespace App\Services\Admin\Export;

use App\Services\Admin\AdminCapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianTabelCapaianDetailExportService
{
    use ExportFormatterTrait;

    protected AdminCapaianMahasiswaService $capaianService;

    public function __construct(AdminCapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = []): void
    {
        $data = $this->capaianService->getDetailTabelCapaianMahasiswa();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Tabel Capaian');

        $headers = ['Kode Prodi', 'Nama Prodi', 'Tahun Angkatan', 'Tren IPS', 'Jumlah MK Gagal', 'Rata-rata SKS Lulus'];

        $this->writeTitleBlock(
            $sheet,
            'DETAIL TABEL CAPAIAN MAHASISWA PER TAHUN ANGKATAN (PRODI ADMIN)',
            'Admin',
            'Prodi Admin saat ini',
            count($headers)
        );

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $rowIndex = 0;
        $rows = $data['data'] ?? [];
        foreach ($rows as $row) {
            $prodi = $row['prodi'] ?? [];
            $sheet->setCellValue('A'.$startRow, $prodi['kode_prodi'] ?? '');
            $sheet->setCellValue('B'.$startRow, $prodi['nama_prodi'] ?? '');
            $sheet->setCellValue('C'.$startRow, $row['tahun_angkatan'] ?? '');
            $sheet->setCellValue('D'.$startRow, $row['tren_ips'] ?? '');
            $sheet->setCellValue('E'.$startRow, $row['jumlah_matakuliah_gagal'] ?? 0);
            $sheet->setCellValue('F'.$startRow, number_format((float) ($row['rata_rata_sks_lulus'] ?? 0), 2));
            $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
            $startRow++;
            $rowIndex++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'Admin_Tabel_Capaian_Detail_'.date('Y-m-d'));
    }
}
