<?php

namespace App\Services\Mahasiswa\Export;

use App\Models\AkademikMahasiswa;
use App\Models\IpsMahasiswa;
use App\Models\KhsKrsMahasiswa;
use App\Models\EarlyWarningSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProfileExportService
{
    /**
     * Export Mahasiswa Profile to XLSX
     */
    public function exportProfile()
    {
        $user = Auth::user();
        $mahasiswa = $user->mahasiswa;

        if (!$mahasiswa) {
            throw new \Exception('Data mahasiswa tidak ditemukan');
        }

        $akademik = AkademikMahasiswa::where('mahasiswa_id', $mahasiswa->id)->first();
        $ips = IpsMahasiswa::where('mahasiswa_id', $mahasiswa->id)->first();
        $ews = $akademik ? EarlyWarningSystem::where('akademik_mahasiswa_id', $akademik->id)->first() : null;
        $khsKrsWithNilaiDE = $this->getMatakuliahWithNilaiDE($mahasiswa->id);
        $progressMk = $this->getProgressMk($akademik);
        $alasanTidakEligible = $this->getAlasanTidakEligible($akademik);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN PROFIL MAHASISWA');
        $sheet->setCellValue('A2', $user->name . ' (' . $mahasiswa->nim . ')');
        $sheet->setCellValue('A3', 'Dicetak: ' . date('d-m-Y H:i'));

        $startRow = 5;

        // === DATA MAHASISWA ===
        $sheet->setCellValue('A' . $startRow, 'DATA MAHASISWA');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $sheet->setCellValue('A' . $startRow, 'Nama');
        $sheet->setCellValue('B' . $startRow, $user->name);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'NIM');
        $sheet->setCellValue('B' . $startRow, $mahasiswa->nim);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Semester Aktif');
        $sheet->setCellValue('B' . $startRow, $akademik->semester_aktif ?? 1);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Tahun Masuk');
        $sheet->setCellValue('B' . $startRow, $akademik->tahun_masuk ?? '-');
        $startRow += 2;

        // === DATA AKADEMIK ===
        $sheet->setCellValue('A' . $startRow, 'DATA AKADEMIK');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $sheet->setCellValue('A' . $startRow, 'IPK');
        $sheet->setCellValue('B' . $startRow, $akademik->ipk ?? 0);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'SKS Lulus');
        $sheet->setCellValue('B' . $startRow, $akademik->sks_lulus ?? 0);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'SKS Tempuh');
        $sheet->setCellValue('B' . $startRow, $akademik->sks_tempuh ?? 0);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'SKS Sekarang');
        $sheet->setCellValue('B' . $startRow, $akademik->sks_now ?? 0);
        $startRow += 2;

        // === STATUS EWS ===
        $sheet->setCellValue('A' . $startRow, 'STATUS EWS');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $sheet->setCellValue('A' . $startRow, 'Status');
        $sheet->setCellValue('B' . $startRow, $ews->status ?? '-');
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Status Kelulusan');
        $sheet->setCellValue('B' . $startRow, $ews->status_kelulusan ?? '-');
        $startRow++;
        if (!empty($alasanTidakEligible)) {
            $sheet->setCellValue('A' . $startRow, 'Alasan Tidak Eligible');
            $sheet->setCellValue('B' . $startRow, implode(', ', $alasanTidakEligible));
            $startRow++;
        }
        $startRow += 2;

        // === IPS PER SEMESTER ===
        $sheet->setCellValue('A' . $startRow, 'IPS PER SEMESTER');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $headers = ['Semester', 'IPS'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($headers));

        $startRow++;
        $ipsData = [];
        for ($i = 1; $i <= 14; $i++) {
            $ipsField = 'ips_' . $i;
            if ($ips && $ips->$ipsField !== null) {
                $ipsData[] = ['semester' => $i, 'ips' => (float) $ips->$ipsField];
            }
        }
        foreach ($ipsData as $data) {
            $sheet->setCellValue('A' . $startRow, $data['semester']);
            $sheet->setCellValue('B' . $startRow, $data['ips']);
            $startRow++;
        }
        $startRow += 2;

        // === MATA KULIAH DENGAN NILAI D/E ===
        $sheet->setCellValue('A' . $startRow, 'MATA KULIAH DENGAN NILAI D/E');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $headers = ['Kode MK', 'Nama MK', 'SKS', 'Semester', 'Kelompok', 'Nilai', 'Nilai Angka'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($headers));

        $startRow++;
        foreach ($khsKrsWithNilaiDE as $mk) {
            $sheet->setCellValue('A' . $startRow, $mk['kode_mk']);
            $sheet->setCellValue('B' . $startRow, $mk['nama_mk']);
            $sheet->setCellValue('C' . $startRow, $mk['sks']);
            $sheet->setCellValue('D' . $startRow, $mk['semester']);
            $sheet->setCellValue('E' . $startRow, $mk['kelompok']);
            $sheet->setCellValue('F' . $startRow, $mk['nilai']);
            $sheet->setCellValue('G' . $startRow, $mk['nilai_angka']);
            $startRow++;
        }
        $startRow += 2;

        // === PROGRESS MK ===
        $sheet->setCellValue('A' . $startRow, 'PROGRESS MK');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $sheet->setCellValue('A' . $startRow, 'MK Nasional');
        $sheet->setCellValue('B' . $startRow, $progressMk['mk_nasional'] === 'yes' ? 'Selesai' : 'Belum Selesai');
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'MK Fakultas');
        $sheet->setCellValue('B' . $startRow, $progressMk['mk_fakultas'] === 'yes' ? 'Selesai' : 'Belum Selesai');
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'MK Prodi');
        $sheet->setCellValue('B' . $startRow, $progressMk['mk_prodi'] === 'yes' ? 'Selesai' : 'Belum Selesai');
        $startRow++;

        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->saveFile($spreadsheet, 'Mahasiswa_Profile_' . $mahasiswa->nim . '_' . date('Y-m-d'));
    }

    private function getMatakuliahWithNilaiDE($mahasiswaId)
    {
        return KhsKrsMahasiswa::join('mata_kuliahs', 'khs_krs_mahasiswa.matakuliah_id', '=', 'mata_kuliahs.id')
            ->join('kelompok_mata_kuliah', 'khs_krs_mahasiswa.kelompok_id', '=', 'kelompok_mata_kuliah.id')
            ->where('khs_krs_mahasiswa.mahasiswa_id', $mahasiswaId)
            ->whereIn('khs_krs_mahasiswa.nilai_akhir_huruf', ['D', 'E'])
            ->whereIn('khs_krs_mahasiswa.id', function ($query) use ($mahasiswaId) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa')
                    ->where('mahasiswa_id', $mahasiswaId)
                    ->groupBy('matakuliah_id');
            })
            ->select('mata_kuliahs.kode as kode_mk', 'mata_kuliahs.name as nama_mk', 'mata_kuliahs.sks', 'mata_kuliahs.semester as semester_mk', 'kelompok_mata_kuliah.kode as kelompok', 'khs_krs_mahasiswa.nilai_akhir_huruf as nilai', 'khs_krs_mahasiswa.nilai_akhir_angka as nilai_angka')
            ->orderBy('mata_kuliahs.semester', 'asc')
            ->get()
            ->map(function ($mk) {
                return [
                    'kode_mk' => $mk->kode_mk,
                    'nama_mk' => $mk->nama_mk,
                    'sks' => $mk->sks,
                    'semester' => $mk->semester_mk,
                    'kelompok' => $mk->kelompok,
                    'nilai' => $mk->nilai,
                    'nilai_angka' => $mk->nilai_angka,
                ];
            })->toArray();
    }

    private function getProgressMk($akademik)
    {
        if (!$akademik) {
            return ['mk_nasional' => 'no', 'mk_fakultas' => 'no', 'mk_prodi' => 'no'];
        }
        return [
            'mk_nasional' => $akademik->mk_nasional ?? 'no',
            'mk_fakultas' => $akademik->mk_fakultason ?? 'no',
            'mk_prodi' => $akademik->mk_prodi ?? 'no',
        ];
    }

    private function getAlasanTidakEligible($akademik)
    {
        if (!$akademik) return [];
        $alasan = [];
        if ($akademik->ipk <= 2.0) $alasan[] = 'IPK kurang dari atau sama dengan 2.0';
        if ($akademik->sks_lulus < 144) $alasan[] = 'SKS Lulus kurang dari 144';
        if ($akademik->mk_nasional !== 'yes') $alasan[] = 'MK Nasional belum diselesaikan';
        if ($akademik->mk_fakultason !== 'yes') $alasan[] = 'MK Fakultas belum diselesaikan';
        if ($akademik->mk_prodi !== 'yes') $alasan[] = 'MK Prodi belum diselesaikan';
        if ($akademik->nilai_e === 'yes') $alasan[] = 'Memiliki nilai E';
        if ($akademik->nilai_d_melebihi_batas === 'yes') $alasan[] = 'Nilai D melebihi batas 5%';
        return $alasan;
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
