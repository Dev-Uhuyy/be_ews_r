<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MahasiswaListExportService
{
    use ExportFormatterTrait;

    /**
     * Export Mahasiswa List to XLSX
     */
    public function exportMahasiswaList($filters = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('List Mahasiswa');

        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Prodi', 'Tahun Masuk', 'SKS Total', 'IPK', 'Nilai D', 'Nilai E', 'Status EWS', 'Status Kelulusan'];
        $filterDesc = $this->buildFilterDescription($filters);

        $this->writeTitleBlock($sheet, 'LAPORAN LIST MAHASISWA', 'Daftar Keseluruhan Mahasiswa', $filterDesc, count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $query = $this->getBaseQuery($filters);
        $mahasiswas = $query->orderBy('prodis.nama', 'asc')
            ->orderBy('users.name', 'asc')
            ->get();

        foreach ($mahasiswas as $i => $mhs) {
            $sheet->setCellValue('A' . $startRow, $i + 1);
            $sheet->setCellValue('B' . $startRow, $mhs->nim);
            $sheet->setCellValue('C' . $startRow, $mhs->nama_mahasiswa);
            $sheet->setCellValue('D' . $startRow, $mhs->kode_prodi . ' - ' . $mhs->nama_prodi);
            $sheet->setCellValue('E' . $startRow, $mhs->tahun_masuk);
            $sheet->setCellValue('F' . $startRow, $mhs->sks_lulus ?? 0);
            $sheet->setCellValue('G' . $startRow, number_format((float)($mhs->ipk ?? 0), 2));
            $sheet->setCellValue('H' . $startRow, $mhs->nilai_d_melebihi_batas ?? 'no');
            $sheet->setCellValue('I' . $startRow, $mhs->nilai_e ?? 'no');
            $sheet->setCellValue('J' . $startRow, $mhs->ews_status ?? '-');
            $sheet->setCellValue('K' . $startRow, $mhs->status_kelulusan ?? '-');

            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Dekan_Mahasiswa_List_' . date('Y-m-d'));
    }

    /**
     * Export Mahasiswa By Status to XLSX
     */
    public function exportMahasiswaByStatus($filters = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mahasiswa by Status');

        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Prodi', 'Kode Prodi', 'Tahun Masuk', 'SKS Total', 'IPK', 'Status Mahasiswa', 'Status EWS', 'Status Kelulusan'];
        $filterDesc = $this->buildFilterDescriptionForStatus($filters);

        $this->writeTitleBlock($sheet, 'LAPORAN MAHASISWA BERDASARKAN STATUS', 'Filter Status Mahasiswa & EWS', $filterDesc, count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $query = $this->getBaseQuery($filters);
        
        // Specific status filters
        if (!empty($filters['status_mahasiswa'])) {
            $statusMhs = strtolower($filters['status_mahasiswa']);
            if (in_array($statusMhs, ['aktif', 'cuti', 'mangkir'])) {
                $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = ?', [$statusMhs]);
            }
        }

        $mahasiswas = $query->orderBy('prodis.nama', 'asc')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->orderBy('users.name', 'asc')
            ->get();

        foreach ($mahasiswas as $i => $mhs) {
            $sheet->setCellValue('A' . $startRow, $i + 1);
            $sheet->setCellValue('B' . $startRow, $mhs->nim);
            $sheet->setCellValue('C' . $startRow, $mhs->nama_mahasiswa);
            $sheet->setCellValue('D' . $startRow, $mhs->nama_prodi);
            $sheet->setCellValue('E' . $startRow, $mhs->kode_prodi);
            $sheet->setCellValue('F' . $startRow, $mhs->tahun_masuk);
            $sheet->setCellValue('G' . $startRow, $mhs->sks_lulus ?? 0);
            $sheet->setCellValue('H' . $startRow, number_format((float)($mhs->ipk ?? 0), 2));
            $sheet->setCellValue('I' . $startRow, $mhs->status_mahasiswa ?? '-');
            $sheet->setCellValue('J' . $startRow, $mhs->ews_status ?? '-');
            $sheet->setCellValue('K' . $startRow, $mhs->status_kelulusan ?? '-');

            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Dekan_Mahasiswa_By_Status_' . date('Y-m-d'));
    }

    private function getBaseQuery($filters)
    {
        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'prodis.nama as nama_prodi',
                    'prodis.kode_prodi',
                    'akademik_mahasiswa.tahun_masuk',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.ipk',
                    'akademik_mahasiswa.nilai_d_melebihi_batas',
                    'akademik_mahasiswa.nilai_e',
                    'mahasiswa.status_mahasiswa',
                    'early_warning_system.status as ews_status',
                    'early_warning_system.status_kelulusan'
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if (!empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        }
        if (!empty($filters['ipk_max']) && is_numeric($filters['ipk_max'])) {
            $query->where('akademik_mahasiswa.ipk', '<', $filters['ipk_max']);
        }
        if (!empty($filters['sks_max']) && is_numeric($filters['sks_max'])) {
            $query->where('akademik_mahasiswa.sks_lulus', '<', $filters['sks_max']);
        }
        if (isset($filters['has_nilai_d'])) {
            $hasNilaiD = filter_var($filters['has_nilai_d'], FILTER_VALIDATE_BOOLEAN);
            $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', $hasNilaiD ? 'yes' : 'no');
        }
        if (isset($filters['has_nilai_e'])) {
            $hasNilaiE = filter_var($filters['has_nilai_e'], FILTER_VALIDATE_BOOLEAN);
            $query->where('akademik_mahasiswa.nilai_e', $hasNilaiE ? 'yes' : 'no');
        }
        if (!empty($filters['status_kelulusan'])) {
            $query->where('early_warning_system.status_kelulusan', $filters['status_kelulusan']);
        }
        if (!empty($filters['ews_status'])) {
            $query->where('early_warning_system.status', $filters['ews_status']);
        }

        return $query;
    }

    private function buildFilterDescription($filters)
    {
        $desc = [];
        if (!empty($filters['prodi_id'])) {
            $prodi = Prodi::find($filters['prodi_id']);
            $desc[] = 'Prodi: ' . ($prodi ? $prodi->nama : $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $desc[] = 'Angkatan: ' . $filters['tahun_masuk'];
        }
        if (!empty($filters['ipk_max'])) {
            $desc[] = 'IPK < ' . $filters['ipk_max'];
        }
        if (!empty($filters['sks_max'])) {
            $desc[] = 'SKS < ' . $filters['sks_max'];
        }
        if (isset($filters['has_nilai_d'])) {
            $desc[] = filter_var($filters['has_nilai_d'], FILTER_VALIDATE_BOOLEAN) ? 'Ada Nilai D' : 'Tanpa Nilai D';
        }
        if (isset($filters['has_nilai_e'])) {
            $desc[] = filter_var($filters['has_nilai_e'], FILTER_VALIDATE_BOOLEAN) ? 'Ada Nilai E' : 'Tanpa Nilai E';
        }
        if (!empty($filters['status_kelulusan'])) {
            $desc[] = 'Kelulusan: ' . ucfirst($filters['status_kelulusan']);
        }
        if (!empty($filters['ews_status'])) {
            $desc[] = 'EWS: ' . str_replace('_', ' ', ucfirst($filters['ews_status']));
        }

        return empty($desc) ? 'Semua Data' : implode(' | ', $desc);
    }

    private function buildFilterDescriptionForStatus($filters)
    {
        $desc = [];
        if (!empty($filters['prodi_id'])) {
            $prodi = Prodi::find($filters['prodi_id']);
            $desc[] = 'Prodi: ' . ($prodi ? $prodi->nama : $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $desc[] = 'Angkatan: ' . $filters['tahun_masuk'];
        }
        if (!empty($filters['status_mahasiswa'])) {
            $desc[] = 'Status: ' . ucfirst($filters['status_mahasiswa']);
        }
        if (!empty($filters['ews_status'])) {
            $desc[] = 'EWS: ' . str_replace('_', ' ', ucfirst($filters['ews_status']));
        }

        return empty($desc) ? 'Semua Data' : implode(' | ', $desc);
    }
}
