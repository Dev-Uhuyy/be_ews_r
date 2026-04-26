<?php

namespace App\Services\Kaprodi\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MahasiswaListExportService
{
    use ExportFormatterTrait;

    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    /**
     * Export Mahasiswa List to XLSX
     */
    public function exportMahasiswaList($filters = [])
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('List Mahasiswa');

        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Tahun Masuk', 'SKS Total', 'IPK', 'Nilai D', 'Nilai E', 'Status EWS', 'Status Kelulusan'];
        $filterDesc = $this->buildFilterDescription($filters);

        $this->writeTitleBlock($sheet, 'LAPORAN LIST MAHASISWA', $prodi->kode_prodi . ' - ' . $prodi->nama, $filterDesc, count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'mahasiswa.status_mahasiswa',
                    'akademik_mahasiswa.tahun_masuk',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.ipk',
                    'akademik_mahasiswa.nilai_d_melebihi_batas',
                    'akademik_mahasiswa.nilai_e',
                    'early_warning_system.status as ews_status',
                    'early_warning_system.status_kelulusan'
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId);

        // Apply filters
        if (!empty($filters['status_mahasiswa'])) {
            $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) = ?', [strtolower($filters['status_mahasiswa'])]);
        } else {
            $query->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');
        }

        if (!empty($filters['tahun_masuk'])) $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        if (!empty($filters['ipk_max']) && is_numeric($filters['ipk_max'])) $query->where('akademik_mahasiswa.ipk', '<', $filters['ipk_max']);
        if (!empty($filters['sks_max']) && is_numeric($filters['sks_max'])) $query->where('akademik_mahasiswa.sks_lulus', '<', $filters['sks_max']);
        if (isset($filters['has_nilai_d'])) $query->where('akademik_mahasiswa.nilai_d_melebihi_batas', filter_var($filters['has_nilai_d'], FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no');
        if (isset($filters['has_nilai_e'])) $query->where('akademik_mahasiswa.nilai_e', filter_var($filters['has_nilai_e'], FILTER_VALIDATE_BOOLEAN) ? 'yes' : 'no');
        if (!empty($filters['status_kelulusan'])) $query->where('early_warning_system.status_kelulusan', $filters['status_kelulusan']);
        if (!empty($filters['ews_status'])) $query->where('early_warning_system.status', $filters['ews_status']);

        // Update headers to include Status Mahasiswa
        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Status Mhs', 'Tahun Masuk', 'SKS Total', 'IPK', 'Nilai D', 'Nilai E', 'Status EWS', 'Status Kelulusan'];
        $this->writeTitleBlock($sheet, 'LAPORAN LIST MAHASISWA', $prodi->kode_prodi . ' - ' . $prodi->nama, $filterDesc, count($headers));
        $this->writeHeaderRow($sheet, 6, $headers);

        $mahasiswas = $query->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')->orderBy('users.name', 'asc')->get();

        foreach ($mahasiswas as $i => $mhs) {
            $sheet->setCellValue('A' . $startRow, $i + 1);
            $sheet->setCellValue('B' . $startRow, $mhs->nim);
            $sheet->setCellValue('C' . $startRow, $mhs->nama_mahasiswa);
            $sheet->setCellValue('D' . $startRow, $mhs->status_mahasiswa);
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
        $this->saveFile($spreadsheet, 'Kaprodi_Mahasiswa_List_' . date('Y-m-d'));
        return; // Fixed: avoid continuing to original code logic
    }

    private function buildFilterDescription($f)
    {
        $d = [];
        if (!empty($f['tahun_masuk'])) $d[] = 'Angkatan: ' . $f['tahun_masuk'];
        if (!empty($f['ipk_max'])) $d[] = 'IPK < ' . $f['ipk_max'];
        if (!empty($f['status_kelulusan'])) $d[] = 'Lulus: ' . $f['status_kelulusan'];
        return empty($d) ? 'Semua Filter' : implode(' | ', $d);
    }
}