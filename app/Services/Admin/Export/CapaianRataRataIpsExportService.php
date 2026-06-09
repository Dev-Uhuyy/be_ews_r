<?php

namespace App\Services\Admin\Export;

use App\Services\Admin\AdminCapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianRataRataIpsExportService
{
    use ExportFormatterTrait;

    protected AdminCapaianMahasiswaService $capaianService;

    public function __construct(AdminCapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = []): void
    {
        $data = $this->capaianService->getRataRataIpsPerTahunProdi();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rata-rata IPS');

        $headers = [
            'Tahun Masuk', 'Jumlah Mahasiswa',
            'IPS 1', 'IPS 2', 'IPS 3', 'IPS 4', 'IPS 5', 'IPS 6', 'IPS 7',
            'IPS 8', 'IPS 9', 'IPS 10', 'IPS 11', 'IPS 12', 'IPS 13', 'IPS 14',
        ];

        $this->writeTitleBlock(
            $sheet,
            'RATA-RATA IPS PER TAHUN ANGKATAN (PRODI ADMIN)',
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
            $ipsPerSemester = $row['ips_per_semester'] ?? [];
            $line = [
                $row['tahun_masuk'] ?? '',
                $row['jumlah_mahasiswa'] ?? 0,
            ];
            for ($i = 1; $i <= 14; $i++) {
                $key = 'ips_'.$i;
                $line[] = isset($ipsPerSemester[$key]) ? number_format((float) $ipsPerSemester[$key], 2) : '';
            }
            $col = 0;
            foreach ($line as $val) {
                $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $val);
                $col++;
            }
            $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
            $startRow++;
            $rowIndex++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'Admin_Rata_Rata_IPS_'.date('Y-m-d'));
    }
}
