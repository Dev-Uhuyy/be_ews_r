<?php

namespace App\Services\Admin\Export;

use App\Services\Admin\AdminCapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianListMatakuliahExportService
{
    use ExportFormatterTrait;

    protected AdminCapaianMahasiswaService $capaianService;

    public function __construct(AdminCapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = [])
    {
        $data = $this->capaianService->getListMataKuliahPerProdi($filters);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('List MK Gagal');

        $tahunMasuk = $filters['tahun_masuk'] ?? null;
        $isGroupedByAngkatan = ! empty($data['total_data']) && isset($data['data'][0]['tahun_angkatan']);

        $headers = $isGroupedByAngkatan
            ? ['Tahun Angkatan', 'Kode MK', 'Nama MK', 'SKS', 'Jumlah Mahasiswa Gagal']
            : ['Kode MK', 'Nama MK', 'SKS', 'Jumlah Mahasiswa Gagal'];

        $scope = $tahunMasuk ? 'Tahun Angkatan: '.$tahunMasuk : 'Semua Tahun Angkatan';

        $this->writeTitleBlock(
            $sheet,
            'LIST MATA KULIAH GAGAL (PRODI ADMIN)',
            'Admin',
            $scope,
            count($headers)
        );

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $rowIndex = 0;
        $rows = $data['data'] ?? [];
        foreach ($rows as $row) {
            $tahunAngkatan = $row['tahun_angkatan'] ?? null;
            $mk = $isGroupedByAngkatan ? ($row['matakuliah'] ?? []) : $row;

            $line = [];
            if ($isGroupedByAngkatan) {
                $line[] = $tahunAngkatan ?? '';
            }
            $line[] = $mk['kode_matakuliah'] ?? '';
            $line[] = $mk['nama_matakuliah'] ?? '';
            $line[] = $mk['sks'] ?? 0;
            $line[] = $mk['jumlah_mahasiswa_gagal'] ?? 0;

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

        return $this->saveFile($spreadsheet, 'Admin_List_Matakuliah_'.date('Y-m-d'));
    }
}
