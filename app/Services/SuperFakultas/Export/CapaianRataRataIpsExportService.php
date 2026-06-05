<?php

namespace App\Services\SuperFakultas\Export;

use App\Services\SuperFakultas\CapaianMahasiswaService;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CapaianRataRataIpsExportService
{
    use ExportFormatterTrait;

    protected CapaianMahasiswaService $capaianService;

    public function __construct(CapaianMahasiswaService $capaianService)
    {
        $this->capaianService = $capaianService;
    }

    public function exportXlsx($filters = [])
    {
        $data = $this->capaianService->getRataRataIpsPerTahunProdi($filters);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rata-rata IPS');

        $headers = [
            'Kode Prodi', 'Nama Prodi', 'Tahun Masuk', 'Jumlah Mahasiswa',
            'IPS 1', 'IPS 2', 'IPS 3', 'IPS 4', 'IPS 5', 'IPS 6', 'IPS 7',
            'IPS 8', 'IPS 9', 'IPS 10', 'IPS 11', 'IPS 12', 'IPS 13', 'IPS 14',
        ];

        $prodiId = $filters['prodi_id'] ?? null;
        $scope = $prodiId ? 'Filter Prodi ID: '.$prodiId : 'Semua Prodi';

        $this->writeTitleBlock(
            $sheet,
            'RATA-RATA IPS PER TAHUN PER PRODI',
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
            $dataPerAngkatan = $prodiData['data_per_angkatan'] ?? [];
            foreach ($dataPerAngkatan as $angkatan) {
                $ipsPerSemester = $angkatan['ips_per_semester'] ?? [];
                $row = [
                    $prodi['kode_prodi'] ?? '',
                    $prodi['nama_prodi'] ?? '',
                    $angkatan['tahun_masuk'] ?? '',
                    $angkatan['jumlah_mahasiswa'] ?? 0,
                ];
                for ($i = 1; $i <= 14; $i++) {
                    $key = 'ips_'.$i;
                    $row[] = isset($ipsPerSemester[$key]) ? number_format((float) $ipsPerSemester[$key], 2) : '';
                }
                $col = 0;
                foreach ($row as $val) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $val);
                    $col++;
                }
                $this->styleDataRow($sheet, $startRow, count($headers), $rowIndex % 2 === 1);
                $startRow++;
                $rowIndex++;
            }
        }

        $this->autoSizeColumns($sheet, count($headers));

        return $this->saveFile($spreadsheet, 'SuperFakultas_Rata_Rata_IPS_'.date('Y-m-d'));
    }
}
