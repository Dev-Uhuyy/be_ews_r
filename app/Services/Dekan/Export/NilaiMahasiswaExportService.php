<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NilaiMahasiswaExportService
{
    /**
     * Export Nilai Mahasiswa Detail to XLSX
     */
    public function exportNilaiDetail($filters = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $filterDesc = $this->buildFilterDescription($filters);
        $sheet->setCellValue('A1', 'LAPORAN DETAIL NILAI MAHASISWA');
        $sheet->setCellValue('A2', $filterDesc);
        $sheet->setCellValue('A3', 'Dicetak: ' . date('d-m-Y H:i'));

        $startRow = 5;

        $isSingleMahasiswa = !empty($filters['mahasiswa_id']);

        // Get data
        $mandatoryMKsByCategory = $this->getMandatoryMKs($filters);
        $mahasiswas = $this->getMahasiswas($filters, $isSingleMahasiswa);

        if ($isSingleMahasiswa) {
            $mhs = $mahasiswas->first();
            if ($mhs) {
                $enriched = $this->enrichMahasiswaNilai($mhs, $mandatoryMKsByCategory);
                $this->writeSingleMahasiswa($sheet, $enriched, $startRow);
            }
        } else {
            $no = 1;
            foreach ($mahasiswas as $mhs) {
                $enriched = $this->enrichMahasiswaNilai($mhs, $mandatoryMKsByCategory);
                $this->writeMahasiswaRow($sheet, $enriched, $no++, $startRow);
                $startRow++;
            }
        }

        // Auto size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $suffix = $isSingleMahasiswa ? '_Mahasiswa_' . $filters['mahasiswa_id'] : '';
        $this->saveFile($spreadsheet, 'Dekan_Nilai_Detail' . $suffix . '_' . date('Y-m-d'));
    }

    /**
     * Export Summary to XLSX
     */
    public function exportNilaiSummary($filters = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $filterDesc = $this->buildFilterDescription($filters);
        $sheet->setCellValue('A1', 'LAPORAN SUMMARY NILAI MAHASISWA');
        $sheet->setCellValue('A2', $filterDesc);
        $sheet->setCellValue('A3', 'Dicetak: ' . date('d-m-Y H:i'));

        $startRow = 5;

        // Summary stats
        $stats = $this->getSummaryStats($filters);

        $headers = ['Total Mahasiswa', 'Punya Nilai D', 'Punya Nilai E', 'MK Nasional Kurang', 'MK Fakultas Kurang'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($headers));

        $startRow++;
        $sheet->setCellValue('A' . $startRow, $stats['total_mahasiswa']);
        $sheet->setCellValue('B' . $startRow, $stats['mahasiswa_dengan_nilai_d']);
        $sheet->setCellValue('C' . $startRow, $stats['mahasiswa_dengan_nilai_e']);
        $sheet->setCellValue('D' . $startRow, $stats['mk_nasional_belum_lulus']);
        $sheet->setCellValue('E' . $startRow, $stats['mk_fakultas_belum_lulus']);

        // Auto size columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->saveFile($spreadsheet, 'Dekan_Nilai_Summary_' . date('Y-m-d'));
    }

    private function writeSingleMahasiswa($sheet, $mhs, $startRow)
    {
        // Student info header
        $sheet->setCellValue('A' . $startRow, 'NIM: ' . $mhs->nim);
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Nama: ' . $mhs->nama_lengkap);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Prodi: ' . $mhs->nama_prodi);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'IPK: ' . $mhs->ipk . ' | SKS Lulus: ' . $mhs->sks_lulus);
        $startRow += 2;

        // Nilai D Section
        $sheet->setCellValue('A' . $startRow, 'MATA KULIAH NILAI D');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $sheet->setCellValue('A' . $startRow, 'Kode');
        $sheet->setCellValue('B' . $startRow, 'Nama');
        $sheet->setCellValue('C' . $startRow, 'SKS');
        $sheet->setCellValue('D' . $startRow, 'Nilai');
        $this->styleHeader($sheet, $startRow, 4);

        $startRow++;
        foreach ($mhs->mata_kuliah_nilai_d as $mk) {
            $sheet->setCellValue('A' . $startRow, $mk['kode']);
            $sheet->setCellValue('B' . $startRow, $mk['nama']);
            $sheet->setCellValue('C' . $startRow, $mk['sks']);
            $sheet->setCellValue('D' . $startRow, $mk['nilai_akhir_huruf']);
            $startRow++;
        }

        // Total SKS Nilai D
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Total SKS Nilai D: ' . $mhs->total_sks_nilai_d);
        $startRow += 2;

        // Nilai E Section
        $sheet->setCellValue('A' . $startRow, 'MATA KULIAH NILAI E');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $sheet->setCellValue('A' . $startRow, 'Kode');
        $sheet->setCellValue('B' . $startRow, 'Nama');
        $sheet->setCellValue('C' . $startRow, 'SKS');
        $sheet->setCellValue('D' . $startRow, 'Nilai');
        $this->styleHeader($sheet, $startRow, 4);

        $startRow++;
        foreach ($mhs->mata_kuliah_nilai_e as $mk) {
            $sheet->setCellValue('A' . $startRow, $mk['kode']);
            $sheet->setCellValue('B' . $startRow, $mk['nama']);
            $sheet->setCellValue('C' . $startRow, $mk['sks']);
            $sheet->setCellValue('D' . $startRow, $mk['nilai_akhir_huruf']);
            $startRow++;
        }

        // Total SKS Nilai E
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Total SKS Nilai E: ' . $mhs->total_sks_nilai_e);
        $startRow += 2;

        // MK Nasional Kurang
        if (!empty($mhs->mk_nasional_kurang)) {
            $sheet->setCellValue('A' . $startRow, 'MK NASIONAL KURANG');
            $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
            $startRow++;

            foreach ($mhs->mk_nasional_kurang as $mk) {
                $sheet->setCellValue('A' . $startRow, $mk['kode'] . ' - ' . $mk['nama'] . ' (' . $mk['sks'] . ' SKS)');
                $startRow++;
            }
            $startRow++;
        }

        // MK Fakultas Kurang
        if (!empty($mhs->mk_fakultas_kurang)) {
            $sheet->setCellValue('A' . $startRow, 'MK FAKULTAS KURANG');
            $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
            $startRow++;

            foreach ($mhs->mk_fakultas_kurang as $mk) {
                $sheet->setCellValue('A' . $startRow, $mk['kode'] . ' - ' . $mk['nama'] . ' (' . $mk['sks'] . ' SKS)');
                $startRow++;
            }
        }
    }

    private function writeMahasiswaRow($sheet, $mhs, $no, $startRow)
    {
        $sheet->setCellValue('A' . $startRow, $no);
        $sheet->setCellValue('B' . $startRow, $mhs->nim);
        $sheet->setCellValue('C' . $startRow, $mhs->nama_lengkap);
        $sheet->setCellValue('D' . $startRow, $mhs->nama_prodi);
        $sheet->setCellValue('E' . $startRow, $mhs->ipk);
        $sheet->setCellValue('F' . $startRow, $mhs->sks_lulus);
        $sheet->setCellValue('G' . $startRow, $mhs->jumlah_nilai_d);
        $sheet->setCellValue('H' . $startRow, $mhs->total_sks_nilai_d);
        $sheet->setCellValue('I' . $startRow, $mhs->jumlah_nilai_e);
        $sheet->setCellValue('J' . $startRow, $mhs->total_sks_nilai_e);
        $sheet->setCellValue('K' . $startRow, $mhs->jumlah_mk_nasional_kurang);
        $sheet->setCellValue('L' . $startRow, $mhs->jumlah_mk_fakultas_kurang);
        $sheet->setCellValue('M' . $startRow, $mhs->total_sks_tidak_lulus);
    }

    private function enrichMahasiswaNilai($mahasiswa, $mandatoryMKsByCategory)
    {
        $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->whereIn('khs1.id', function($query) use ($mahasiswa) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('khs_krs_mahasiswa as khs2')
                    ->where('khs2.mahasiswa_id', $mahasiswa->mahasiswa_id)
                    ->groupBy('khs2.matakuliah_id');
            })
            ->where('khs1.mahasiswa_id', $mahasiswa->mahasiswa_id)
            ->select('mata_kuliahs.id as matakuliah_id', 'mata_kuliahs.kode', 'mata_kuliahs.name as nama', 'mata_kuliahs.sks', 'mata_kuliahs.tipe_mk', 'khs1.nilai_akhir_huruf', 'khs1.nilai_akhir_angka')
            ->get();

        $matkulNilaiD = $latestKhs->where('nilai_akhir_huruf', 'D');
        $mahasiswa->mata_kuliah_nilai_d = $matkulNilaiD->map(function ($mk) {
            return ['kode' => $mk->kode, 'nama' => $mk->nama, 'sks' => $mk->sks, 'nilai_akhir_huruf' => $mk->nilai_akhir_huruf, 'nilai_akhir_angka' => $mk->nilai_akhir_angka];
        })->values()->toArray();
        $mahasiswa->jumlah_nilai_d = $matkulNilaiD->count();
        $mahasiswa->total_sks_nilai_d = $matkulNilaiD->sum('sks');

        $matkulNilaiE = $latestKhs->where('nilai_akhir_huruf', 'E');
        $mahasiswa->mata_kuliah_nilai_e = $matkulNilaiE->map(function ($mk) {
            return ['kode' => $mk->kode, 'nama' => $mk->nama, 'sks' => $mk->sks, 'nilai_akhir_huruf' => $mk->nilai_akhir_huruf, 'nilai_akhir_angka' => $mk->nilai_akhir_angka];
        })->values()->toArray();
        $mahasiswa->jumlah_nilai_e = $matkulNilaiE->count();
        $mahasiswa->total_sks_nilai_e = $matkulNilaiE->sum('sks');

        if ($mahasiswa->mk_nasional === 'no') {
            $nasionalMandatory = $mandatoryMKsByCategory->get('nasional') ?? collect();
            $missingNasional = [];
            foreach ($nasionalMandatory as $mk) {
                $studentGrade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                if (!$studentGrade || $studentGrade->nilai_akhir_huruf === 'E') {
                    $missingNasional[] = ['kode' => $mk->kode, 'nama' => $mk->name, 'sks' => $mk->sks];
                }
            }
            $mahasiswa->mk_nasional_kurang = $missingNasional;
            $mahasiswa->jumlah_mk_nasional_kurang = count($missingNasional);
        } else {
            $mahasiswa->mk_nasional_kurang = [];
            $mahasiswa->jumlah_mk_nasional_kurang = 0;
        }

        if ($mahasiswa->mk_fakultas === 'no') {
            $fakultasMandatory = $mandatoryMKsByCategory->get('fakultas') ?? collect();
            $missingfakultas = [];
            foreach ($fakultasMandatory as $mk) {
                $studentGrade = $latestKhs->firstWhere('matakuliah_id', $mk->id);
                if (!$studentGrade || $studentGrade->nilai_akhir_huruf === 'E') {
                    $missingfakultas[] = ['kode' => $mk->kode, 'nama' => $mk->name, 'sks' => $mk->sks];
                }
            }
            $mahasiswa->mk_fakultas_kurang = $missingfakultas;
            $mahasiswa->jumlah_mk_fakultas_kurang = count($missingfakultas);
        } else {
            $mahasiswa->mk_fakultas_kurang = [];
            $mahasiswa->jumlah_mk_fakultas_kurang = 0;
        }

        $mahasiswa->total_sks_tidak_lulus = $mahasiswa->total_sks_nilai_d + $mahasiswa->total_sks_nilai_e;

        return $mahasiswa;
    }

    private function getMandatoryMKs($filters)
    {
        $mandatoryQuery = DB::table('mata_kuliahs')
            ->whereIn('tipe_mk', ['nasional', 'fakultas']);

        if (!empty($filters['prodi_id'])) {
            $mandatoryQuery->where('prodi_id', $filters['prodi_id']);
        }

        return $mandatoryQuery->get()->groupBy('tipe_mk');
    }

    private function getMahasiswas($filters, $isSingleMahasiswa)
    {
        $query = AkademikMahasiswa::query()
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
            ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select(
                'prodis.nama as nama_prodi',
                'mahasiswa.id as mahasiswa_id',
                'mahasiswa.nim',
                'users.name as nama_lengkap',
                'akademik_mahasiswa.ipk',
                'akademik_mahasiswa.sks_lulus',
                'akademik_mahasiswa.mk_nasional',
                'akademik_mahasiswa.mk_fakultas'
            );

        if (!empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        }
        if (!empty($filters['mahasiswa_id'])) {
            $query->where('mahasiswa.id', $filters['mahasiswa_id']);
        }

        if ($isSingleMahasiswa) {
            return $query->limit(1)->get();
        }

        return $query->orderBy('mahasiswa.nim', 'asc')->get();
    }

    private function getSummaryStats($filters)
    {
        $query = AkademikMahasiswa::query()
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select(
                DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as total_mahasiswa'),
                DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_d_melebihi_batas = "yes" THEN 1 ELSE 0 END) as mahasiswa_dengan_nilai_d'),
                DB::raw('SUM(CASE WHEN akademik_mahasiswa.nilai_e = "yes" THEN 1 ELSE 0 END) as mahasiswa_dengan_nilai_e'),
                DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_nasional = "no" THEN 1 ELSE 0 END) as mk_nasional_belum_lulus'),
                DB::raw('SUM(CASE WHEN akademik_mahasiswa.mk_fakultas = "no" THEN 1 ELSE 0 END) as mk_fakultas_belum_lulus')
            );

        if (!empty($filters['prodi_id'])) {
            $query->where('mahasiswa.prodi_id', $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $query->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        }

        return $query->first()->toArray();
    }

    private function buildFilterDescription($filters)
    {
        $desc = [];
        if (!empty($filters['prodi_id'])) {
            $prodi = \App\Models\Prodi::find($filters['prodi_id']);
            $desc[] = 'Prodi: ' . ($prodi ? $prodi->nama : $filters['prodi_id']);
        }
        if (!empty($filters['tahun_masuk'])) {
            $desc[] = 'Tahun Masuk: ' . $filters['tahun_masuk'];
        }
        if (!empty($filters['mahasiswa_id'])) {
            $desc[] = 'Mahasiswa ID: ' . $filters['mahasiswa_id'];
        }
        return empty($desc) ? 'Semua Data' : implode(' | ', $desc);
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
