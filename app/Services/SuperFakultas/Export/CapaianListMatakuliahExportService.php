<?php

namespace App\Services\SuperFakultas\Export;

use App\Services\SuperFakultas\CapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianListMatakuliahExportService
{
    use ExportFormatterTrait;

    protected CapaianMahasiswaService $capaianService;

    public function __construct(CapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = []): void
    {
        $data = $this->capaianService->getListMataKuliahPerProdi($filters);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('List MK Gagal');

        $headers = ['Kode Prodi', 'Nama Prodi', 'Tahun Angkatan', 'Kode MK', 'Nama MK', 'SKS', 'Jumlah Mahasiswa Gagal'];

        $prodiId = $filters['prodi_id'] ?? null;
        $tahunMasuk = $filters['tahun_masuk'] ?? null;
        $scope = $prodiId ? 'Filter Prodi ID: '.$prodiId : 'Semua Prodi';
        if ($tahunMasuk) {
            $scope .= ' | Tahun Angkatan: '.$tahunMasuk;
        }

        $this->writeTitleBlock(
            $sheet,
            'LIST MATA KULIAH GAGAL PER PRODI',
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
            $tahunAngkatan = $prodiData['tahun_angkatan'] ?? null;
            $matakuliahList = $prodiData['matakuliah'] ?? [];
            foreach ($matakuliahList as $mk) {
                $sheet->setCellValue('A'.$startRow, $prodi['kode_prodi'] ?? '');
                $sheet->setCellValue('B'.$startRow, $prodi['nama_prodi'] ?? '');
                $sheet->setCellValue('C'.$startRow, $tahunAngkatan ?? '-');
                $sheet->setCellValue('D'.$startRow, $mk['kode_matakuliah'] ?? '');
                $sheet->setCellValue('E'.$startRow, $mk['nama_matakuliah'] ?? '');
                $sheet->setCellValue('F'.$startRow, $mk['sks'] ?? 0);
                $sheet->setCellValue('G'.$startRow, $mk['jumlah_mahasiswa_gagal'] ?? 0);
                $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
                $startRow++;
                $rowIndex++;
            }
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'SuperFakultas_List_Matakuliah_'.date('Y-m-d'));
    }
}
