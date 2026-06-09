<?php

namespace App\Services\Admin\Export;

use App\Services\Admin\AdminCapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianMahasiswaGagalExportService
{
    use ExportFormatterTrait;

    protected AdminCapaianMahasiswaService $capaianService;

    public function __construct(AdminCapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = []): void
    {
        $data = $this->capaianService->getListMahasiswaGagalPerMataKuliah($filters);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mahasiswa Gagal');

        $headers = [
            'Tahun Angkatan', 'Dosen Pengampu',
            'Kode Kelompok', 'Jumlah Mahasiswa', 'NIM', 'Nama Mahasiswa', 'Presensi (%)',
        ];

        $matakuliahId = $filters['matakuliah_id'] ?? null;
        $tahunMasuk = $filters['tahun_masuk'] ?? null;
        $scope = 'Matakuliah ID: '.($matakuliahId ?? '-');
        if ($tahunMasuk) {
            $scope .= ' | Tahun Angkatan: '.$tahunMasuk;
        }

        $this->writeTitleBlock(
            $sheet,
            'LIST MAHASISWA GAGAL PER MATA KULIAH (PRODI ADMIN)',
            'Admin',
            $scope,
            count($headers)
        );

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $rowIndex = 0;
        $groups = $data['data'] ?? [];
        foreach ($groups as $group) {
            $mahasiswaList = $group['mahasiswa'] ?? [];
            foreach ($mahasiswaList as $m) {
                $sheet->setCellValue('A'.$startRow, $group['tahun_angkatan'] ?? '');
                $sheet->setCellValue('B'.$startRow, $group['dosen_pengampu'] ?? '');
                $sheet->setCellValue('C'.$startRow, $group['kode_kelompok'] ?? '');
                $sheet->setCellValue('D'.$startRow, $group['jumlah_mahasiswa'] ?? 0);
                $sheet->setCellValue('E'.$startRow, $m['nim'] ?? '');
                $sheet->setCellValue('F'.$startRow, $m['nama_mahasiswa'] ?? '');
                $sheet->setCellValue('G'.$startRow, $m['presensi'] ?? 0);
                $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
                $startRow++;
                $rowIndex++;
            }
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'Admin_Mahasiswa_Gagal_'.date('Y-m-d'));
    }
}
