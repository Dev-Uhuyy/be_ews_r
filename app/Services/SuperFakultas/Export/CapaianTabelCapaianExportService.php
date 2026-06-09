<?php

namespace App\Services\SuperFakultas\Export;

use App\Services\SuperFakultas\CapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianTabelCapaianExportService
{
    use ExportFormatterTrait;

    protected CapaianMahasiswaService $capaianService;

    public function __construct(CapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = []): void
    {
        $data = $this->capaianService->getTabelCapaianMahasiswa($filters);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tabel Capaian');

        $headers = ['Kode Prodi', 'Nama Prodi', 'Tren IPS', 'Jumlah MK Gagal', 'Rata-rata SKS Lulus'];

        $prodiId = $filters['prodi_id'] ?? null;
        $scope = $prodiId ? 'Filter Prodi ID: '.$prodiId : 'Semua Prodi';

        $this->writeTitleBlock(
            $sheet,
            'TABEL CAPAIAN MAHASISWA PER PRODI',
            'SuperFakultas',
            $scope,
            count($headers)
        );

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $rowIndex = 0;
        $dataPerProdi = $data['data_per_prodi'] ?? [];
        foreach ($dataPerProdi as $prodiData) {
            $prodi = $prodiData['prodi'] ?? [];
            $sheet->setCellValue('A'.$startRow, $prodi['kode_prodi'] ?? '');
            $sheet->setCellValue('B'.$startRow, $prodi['nama_prodi'] ?? '');
            $sheet->setCellValue('C'.$startRow, $prodiData['tren_ips'] ?? '');
            $sheet->setCellValue('D'.$startRow, $prodiData['jumlah_matakuliah_gagal'] ?? 0);
            $sheet->setCellValue('E'.$startRow, number_format((float) ($prodiData['rata_rata_sks_lulus'] ?? 0), 2));
            $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
            $startRow++;
            $rowIndex++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'SuperFakultas_Tabel_Capaian_'.date('Y-m-d'));
    }
}
