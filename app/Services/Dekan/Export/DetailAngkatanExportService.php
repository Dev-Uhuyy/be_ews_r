<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use App\Models\KhsKrsMahasiswa;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DetailAngkatanExportService
{
    /**
     * Export Detail Angkatan to XLSX
     *
     * @param array $filters Keys: tahunMasuk (required), prodi_id (optional)
     */
    public function exportDetailAngkatan($filters = [])
    {
        $tahunMasuk = $filters['tahunMasuk'] ?? null;
        $prodiId = $filters['prodi_id'] ?? null;

        if (!$tahunMasuk) {
            throw new \Exception('Tahun masuk wajib diisi');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $prodiNama = $prodiId ? \App\Models\Prodi::find($prodiId)->nama : 'Semua Prodi';

        $sheet->setCellValue('A1', 'LAPORAN DETAIL ANGKATAN');
        $sheet->setCellValue('A2', 'Tahun Masuk: ' . $tahunMasuk);
        $sheet->setCellValue('A3', 'Program Studi: ' . $prodiNama);
        $sheet->setCellValue('A4', 'Dicetak: ' . date('d-m-Y H:i'));

        $startRow = 6;

        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'SKS Total', 'IPK', 'Nilai D (Jumlah)', 'Nilai D (SKS)', 'Nilai E (Jumlah)', 'Nilai E (SKS)', 'MK Nasional', 'MK Fakultas', 'MK Prodi', 'Eligible'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($headers));

        $startRow++;
        $no = 1;

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

        foreach ($mahasiswas as $mhs) {
            $nilaiDetail = $this->getNilaiDetail($mhs->mahasiswa_id);
            $mkStatus = $this->getMkStatus($mhs->mahasiswa_id);

            $sheet->setCellValue('A' . $startRow, $no++);
            $sheet->setCellValue('B' . $startRow, $mhs->nim);
            $sheet->setCellValue('C' . $startRow, $mhs->nama_mahasiswa);
            $sheet->setCellValue('D' . $startRow, $mhs->sks_lulus ?? 0);
            $sheet->setCellValue('E' . $startRow, $mhs->ipk ?? 0);
            $sheet->setCellValue('F' . $startRow, $nilaiDetail['jumlah_nilai_d']);
            $sheet->setCellValue('G' . $startRow, $nilaiDetail['total_sks_nilai_d']);
            $sheet->setCellValue('H' . $startRow, $nilaiDetail['jumlah_nilai_e']);
            $sheet->setCellValue('I' . $startRow, $nilaiDetail['total_sks_nilai_e']);
            $sheet->setCellValue('J' . $startRow, $mkStatus['mk_nasional']);
            $sheet->setCellValue('K' . $startRow, $mkStatus['mk_fakultas']);
            $sheet->setCellValue('L' . $startRow, $mkStatus['mk_prodi']);
            $sheet->setCellValue('M' . $startRow, $mhs->eligible ?? 'noneligible');

            $startRow++;
        }

        // Auto size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

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

        $jumlahNilaiD = 0;
        $totalSksNilaiD = 0;
        $jumlahNilaiE = 0;
        $totalSksNilaiE = 0;

        foreach ($khsData as $khs) {
            if ($khs->nilai_akhir_huruf === 'D') {
                $jumlahNilaiD++;
                $totalSksNilaiD += $khs->sks ?? 0;
            } elseif ($khs->nilai_akhir_huruf === 'E') {
                $jumlahNilaiE++;
                $totalSksNilaiE += $khs->sks ?? 0;
            }
        }

        return [
            'jumlah_nilai_d' => $jumlahNilaiD,
            'total_sks_nilai_d' => $totalSksNilaiD,
            'jumlah_nilai_e' => $jumlahNilaiE,
            'total_sks_nilai_e' => $totalSksNilaiE,
        ];
    }

    private function getMkStatus($mahasiswaId)
    {
        $akademik = AkademikMahasiswa::where('mahasiswa_id', $mahasiswaId)->first();

        return [
            'mk_nasional' => $akademik->mk_nasional ?? 'no',
            'mk_fakultas' => $akademik->mk_fakultason ?? 'no',
            'mk_prodi' => $akademik->mk_prodi ?? 'no',
        ];
    }

    private function styleHeader($sheet, $row, $colCount)
    {
        $styleArray = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCCCCC']],
        ];
        $endCol = chr(64 + $colCount);
        $sheet->getStyle('A' . $row . ':' . $endCol . $row)->applyFromArray($styleArray);
    }

    private function saveFile($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);
        $filename = $filename . '.xlsx';
        $tempPath = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $writer->save($tempPath);

        response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->send();
    }
}
