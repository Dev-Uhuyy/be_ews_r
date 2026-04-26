<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use App\Models\KhsKrsMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DetailAngkatanExportService
{
    use ExportFormatterTrait;

    public function exportDetailAngkatan($filters = [])
    {
        $tahunMasuk = $filters['tahunMasuk'] ?? null;
        $prodiId = $filters['prodi_id'] ?? null;

        if (!$tahunMasuk) {
            throw new \Exception('Tahun masuk wajib diisi');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Angkatan');

        $prodiNama = $prodiId ? Prodi::find($prodiId)->nama : 'Semua Prodi';
        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'SKS Total', 'IPK', 'Nilai D (Jml)', 'Nilai D (SKS)', 'Nilai E (Jml)', 'Nilai E (SKS)', 'MK Nas', 'MK Fak', 'MK Pro', 'Eligible'];

        $this->writeTitleBlock($sheet, 'LAPORAN DETAIL ANGKATAN', 'Angkatan: ' . $tahunMasuk, 'Prodi: ' . $prodiNama, count($headers));

        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers);
        $startRow++;

        $query = AkademikMahasiswa::select(
                    'mahasiswa.id as mahasiswa_id',
                    'mahasiswa.nim',
                    'users.name as nama_mahasiswa',
                    'akademik_mahasiswa.sks_lulus',
                    'akademik_mahasiswa.ipk',
                    DB::raw('early_warning_system.status_kelulusan as eligible')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($prodiId) {
            $query->where('mahasiswa.prodi_id', $prodiId);
        }

        $mahasiswas = $query->orderBy('users.name', 'asc')->get();

        foreach ($mahasiswas as $i => $mhs) {
            $nilaiDetail = $this->getNilaiDetail($mhs->mahasiswa_id);
            $mkStatus = $this->getMkStatus($mhs->mahasiswa_id);

            $sheet->setCellValue('A' . $startRow, $i + 1);
            $sheet->setCellValue('B' . $startRow, $mhs->nim);
            $sheet->setCellValue('C' . $startRow, $mhs->nama_mahasiswa);
            $sheet->setCellValue('D' . $startRow, $mhs->sks_lulus ?? 0);
            $sheet->setCellValue('E' . $startRow, number_format((float)($mhs->ipk ?? 0), 2));
            $sheet->setCellValue('F' . $startRow, $nilaiDetail['jumlah_nilai_d']);
            $sheet->setCellValue('G' . $startRow, $nilaiDetail['total_sks_nilai_d']);
            $sheet->setCellValue('H' . $startRow, $nilaiDetail['jumlah_nilai_e']);
            $sheet->setCellValue('I' . $startRow, $nilaiDetail['total_sks_nilai_e']);
            $sheet->setCellValue('J' . $startRow, strtoupper($mkStatus['mk_nasional']));
            $sheet->setCellValue('K' . $startRow, strtoupper($mkStatus['mk_fakultas']));
            $sheet->setCellValue('L' . $startRow, strtoupper($mkStatus['mk_prodi']));
            $sheet->setCellValue('M' . $startRow, $mhs->eligible ?? 'noneligible');

            $this->styleDataRow($sheet, $startRow, count($headers), $i % 2 === 1);
            $startRow++;
        }

        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Dekan_Detail_Angkatan_' . $tahunMasuk . '_' . date('Y-m-d'));
    }

    private function getNilaiDetail($mahasiswaId)
    {
        $khsData = KhsKrsMahasiswa::join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
            ->where('khs_krs_mahasiswa.mahasiswa_id', $mahasiswaId)
            ->whereIn('khs_krs_mahasiswa.id', function ($query) use ($mahasiswaId) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa')
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->groupBy('matakuliah_id');
            })
            ->select('khs_krs_mahasiswa.nilai_akhir_huruf', 'mata_kuliahs.sks')
            ->get();

        $stats = [
            'jumlah_nilai_d' => 0, 'total_sks_nilai_d' => 0,
            'jumlah_nilai_e' => 0, 'total_sks_nilai_e' => 0
        ];

        foreach ($khsData as $khs) {
            if ($khs->nilai_akhir_huruf === 'D') {
                $stats['jumlah_nilai_d']++;
                $stats['total_sks_nilai_d'] += $khs->sks ?? 0;
            } elseif ($khs->nilai_akhir_huruf === 'E') {
                $stats['jumlah_nilai_e']++;
                $stats['total_sks_nilai_e'] += $khs->sks ?? 0;
            }
        }
        return $stats;
    }

    private function getMkStatus($mahasiswaId)
    {
        $akademik = AkademikMahasiswa::where('mahasiswa_id', $mahasiswaId)->first();
        return [
            'mk_nasional' => $akademik->mk_nasional ?? 'no',
            'mk_fakultas' => $akademik->mk_fakultas ?? 'no',
            'mk_prodi' => $akademik->mk_prodi ?? 'no',
        ];
    }
}
