<?php

namespace App\Services\Admin\Export;

use App\Services\Admin\AdminCapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianTopMatakuliahExportService
{
    use ExportFormatterTrait;

    protected AdminCapaianMahasiswaService $capaianService;

    public function __construct(AdminCapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = []): void
    {
        $data = $this->capaianService->getTop10MatakuliahGagal($filters);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Top MK Gagal');

        $headers = ['Kode Prodi', 'Nama Prodi', 'Kode MK', 'Nama MK', 'SKS', 'Jumlah Mahasiswa Gagal'];

        $tahunMasuk = $filters['tahun_masuk'] ?? null;
        $scope = $tahunMasuk ? 'Tahun Angkatan: '.$tahunMasuk : 'Semua Tahun Angkatan';

        $this->writeTitleBlock(
            $sheet,
            'TOP 10 MATA KULIAH GAGAL (PRODI ADMIN)',
            'Admin',
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
            $top = $prodiData['top_matakuliah_gagal'] ?? [];
            foreach ($top as $mk) {
                $sheet->setCellValue('A'.$startRow, $prodi['kode_prodi'] ?? '');
                $sheet->setCellValue('B'.$startRow, $prodi['nama_prodi'] ?? '');
                $sheet->setCellValue('C'.$startRow, $mk['kode_matakuliah'] ?? '');
                $sheet->setCellValue('D'.$startRow, $mk['nama_matakuliah'] ?? '');
                $sheet->setCellValue('E'.$startRow, $mk['sks'] ?? 0);
                $sheet->setCellValue('F'.$startRow, $mk['jumlah_mahasiswa_gagal'] ?? 0);
                $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
                $startRow++;
                $rowIndex++;
            }
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'Admin_Top_Matakuliah_Gagal_'.date('Y-m-d'));
    }
}
