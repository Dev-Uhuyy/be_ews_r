<?php

namespace App\Services\Admin\Export;

use App\Services\Admin\AdminCapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianTabelCapaianExportService
{
    use ExportFormatterTrait;

    protected AdminCapaianMahasiswaService $capaianService;

    public function __construct(AdminCapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = [])
    {
        $data = $this->capaianService->getTabelCapaianMahasiswa();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tabel Capaian');

        $headers = ['Tren IPS', 'Jumlah MK Gagal'];

        $this->writeTitleBlock(
            $sheet,
            'TABEL CAPAIAN MAHASISWA (PRODI ADMIN)',
            'Admin',
            'Prodi Admin saat ini',
            count($headers)
        );

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $rowIndex = 0;
        $rows = [
            [
                'tren_ips' => $data['tren_ips'] ?? 'Stabil',
                'jumlah_matakuliah_gagal' => $data['jumlah_matakuliah_gagal'] ?? 0,
            ],
        ];
        foreach ($rows as $row) {
            $sheet->setCellValue('A'.$startRow, $row['tren_ips']);
            $sheet->setCellValue('B'.$startRow, $row['jumlah_matakuliah_gagal']);
            $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
            $startRow++;
            $rowIndex++;
        }

        $this->autoSizeColumns($sheet, count($headers));

        return $this->saveFile($spreadsheet, 'Admin_Tabel_Capaian_'.date('Y-m-d'));
    }
}
