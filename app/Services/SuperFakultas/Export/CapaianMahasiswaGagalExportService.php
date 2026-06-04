<?php

namespace App\Services\SuperFakultas\Export;

use App\Services\SuperFakultas\CapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianMahasiswaGagalExportService
{
    use ExportFormatterTrait;

    protected CapaianMahasiswaService $capaianService;

    public function __construct(CapaianMahasiswaService $capaianService)
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
            'Kode Prodi', 'Nama Prodi', 'Tahun Angkatan', 'Dosen Pengampu',
            'Kode Kelompok', 'Jumlah Mahasiswa', 'NIM', 'Nama Mahasiswa', 'Presensi (%)',
        ];

        $prodiId = $filters['prodi_id'] ?? null;
        $tahunMasuk = $filters['tahun_masuk'] ?? null;
        $matakuliahId = $filters['matakuliah_id'] ?? null;
        $scope = 'Matakuliah ID: '.($matakuliahId ?? '-');
        if ($prodiId) {
            $scope .= ' | Prodi ID: '.$prodiId;
        }
        if ($tahunMasuk) {
            $scope .= ' | Tahun Angkatan: '.$tahunMasuk;
        }

        $this->writeTitleBlock(
            $sheet,
            'LIST MAHASISWA GAGAL PER MATA KULIAH',
            'SuperFakultas',
            $scope,
            count($headers)
        );

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $rowIndex = 0;
        $groups = $data['data'] ?? [];
        foreach ($groups as $group) {
            $prodi = $group['prodi'] ?? [];
            $mahasiswaList = $group['mahasiswa'] ?? [];
            foreach ($mahasiswaList as $m) {
                $sheet->setCellValue('A'.$startRow, $prodi['kode_prodi'] ?? '');
                $sheet->setCellValue('B'.$startRow, $prodi['nama_prodi'] ?? '');
                $sheet->setCellValue('C'.$startRow, $group['tahun_angkatan'] ?? '');
                $sheet->setCellValue('D'.$startRow, $group['dosen_pengampu'] ?? '');
                $sheet->setCellValue('E'.$startRow, $group['kode_kelompok'] ?? '');
                $sheet->setCellValue('F'.$startRow, $group['jumlah_mahasiswa'] ?? 0);
                $sheet->setCellValue('G'.$startRow, $m['nim'] ?? '');
                $sheet->setCellValue('H'.$startRow, $m['nama_mahasiswa'] ?? '');
                $sheet->setCellValue('I'.$startRow, $m['presensi'] ?? 0);
                $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
                $startRow++;
                $rowIndex++;
            }
        }

        $this->autoSizeColumns($sheet, count($headers));
        return $this->saveFile($spreadsheet, 'SuperFakultas_Mahasiswa_Gagal_'.date('Y-m-d'));
    }
}
