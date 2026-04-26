<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Prodi;
use App\Services\Traits\ExportFormatterTrait;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class NilaiMahasiswaExportService
{
    use ExportFormatterTrait;

    public function exportNilaiDetail($filters = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Nilai');

        $isSingleMahasiswa = !empty($filters['mahasiswa_id']);
        $headers = ['No', 'NIM', 'Nama Mahasiswa', 'Prodi', 'IPK', 'SKS Lulus', 'Jml D', 'SKS D', 'Jml E', 'SKS E', 'MK Nas', 'MK Fak', 'SKS Tdk Lulus'];
        $filterDesc = $this->buildFilterDescription($filters);

        $this->writeTitleBlock($sheet, 'LAPORAN DETAIL NILAI MAHASISWA', 'Analisis Nilai D, E & Kelengkapan MK', $filterDesc, count($headers));

        $startRow = 6;
        $mandatoryMKsByCategory = $this->getMandatoryMKs($filters);
        $mahasiswas = $this->getMahasiswas($filters, $isSingleMahasiswa);

        if ($isSingleMahasiswa && $mhs = $mahasiswas->first()) {
            $this->writeSingleMahasiswa($sheet, $this->enrichMahasiswaNilai($mhs, $mandatoryMKsByCategory), $startRow);
        } else {
            $this->writeHeaderRow($sheet, $startRow, $headers);
            $startRow++;
            foreach ($mahasiswas as $i => $mhs) {
                $enriched = $this->enrichMahasiswaNilai($mhs, $mandatoryMKsByCategory);
                $this->writeMahasiswaRow($sheet, $enriched, $i + 1, $startRow, count($headers), $i % 2 === 1);
                $startRow++;
            }
        }

        $this->autoSizeColumns($sheet, count($headers));
        $suffix = $isSingleMahasiswa ? '_Mhs_' . $filters['mahasiswa_id'] : '';
        $this->saveFile($spreadsheet, 'Dekan_Nilai_Detail' . $suffix . '_' . date('Y-m-d'));
    }

    private function writeSingleMahasiswa($sheet, $mhs, $startRow)
    {
        $this->writeSectionHeader($sheet, $startRow, 'INFORMASI MAHASISWA', 4);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'NIM: ' . $mhs->nim);
        $sheet->setCellValue('C' . $startRow, 'Nama: ' . $mhs->nama_lengkap);
        $startRow++;
        $sheet->setCellValue('A' . $startRow, 'Prodi: ' . $mhs->nama_prodi);
        $sheet->setCellValue('C' . $startRow, 'IPK: ' . $mhs->ipk . ' | SKS: ' . $mhs->sks_lulus);
        $startRow += 2;

        $headers = ['Kode', 'Nama Mata Kuliah', 'SKS', 'Nilai'];
        
        // Nilai D
        $this->writeSectionHeader($sheet, $startRow, 'MATA KULIAH NILAI D', 4); $startRow++;
        $this->writeHeaderRow($sheet, $startRow, $headers); $startRow++;
        foreach ($mhs->mata_kuliah_nilai_d as $i => $mk) {
            $sheet->setCellValue('A' . $startRow, $mk['kode']);
            $sheet->setCellValue('B' . $startRow, $mk['nama']);
            $sheet->setCellValue('C' . $startRow, $mk['sks']);
            $sheet->setCellValue('D' . $startRow, $mk['nilai_akhir_huruf']);
            $this->styleDataRow($sheet, $startRow, 4, $i % 2 === 1); $startRow++;
        }
        $sheet->setCellValue('A' . $startRow, 'Total SKS Nilai D: ' . $mhs->total_sks_nilai_d);
        $sheet->getStyle('A' . $startRow)->getFont()->setBold(true);
        $startRow += 2;

        // Nilai E
        $this->writeSectionHeader($sheet, $startRow, 'MATA KULIAH NILAI E', 4); $startRow++;
        $this->writeHeaderRow($sheet, $startRow, $headers); $startRow++;
        foreach ($mhs->mata_kuliah_nilai_e as $i => $mk) {
            $sheet->setCellValue('A' . $startRow, $mk['kode']);
            $sheet->setCellValue('B' . $startRow, $mk['nama']);
            $sheet->setCellValue('C' . $startRow, $mk['sks']);
            $sheet->setCellValue('D' . $startRow, $mk['nilai_akhir_huruf']);
            $this->styleDataRow($sheet, $startRow, 4, $i % 2 === 1); $startRow++;
        }
        $sheet->setCellValue('A' . $startRow, 'Total SKS Nilai E: ' . $mhs->total_sks_nilai_e);
        $sheet->getStyle('A' . $startRow)->getFont()->setBold(true);
        $startRow += 2;

        // Missing MKs
        if (!empty($mhs->mk_nasional_kurang)) {
            $this->writeSectionHeader($sheet, $startRow, 'MK NASIONAL KURANG', 4); $startRow++;
            foreach ($mhs->mk_nasional_kurang as $mk) {
                $sheet->setCellValue('A' . $startRow, $mk['kode'] . ' - ' . $mk['nama'] . ' (' . $mk['sks'] . ' SKS)');
                $startRow++;
            }
            $startRow++;
        }
    }

    private function writeMahasiswaRow($sheet, $mhs, $no, $row, $colCount, $isAlt)
    {
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, $mhs->nim);
        $sheet->setCellValue('C' . $row, $mhs->nama_lengkap);
        $sheet->setCellValue('D' . $row, $mhs->nama_prodi);
        $sheet->setCellValue('E' . $row, number_format((float)$mhs->ipk, 2));
        $sheet->setCellValue('F' . $row, $mhs->sks_lulus);
        $sheet->setCellValue('G' . $row, $mhs->jumlah_nilai_d);
        $sheet->setCellValue('H' . $row, $mhs->total_sks_nilai_d);
        $sheet->setCellValue('I' . $row, $mhs->jumlah_nilai_e);
        $sheet->setCellValue('J' . $row, $mhs->total_sks_nilai_e);
        $sheet->setCellValue('K' . $row, $mhs->jumlah_mk_nasional_kurang);
        $sheet->setCellValue('L' . $row, $mhs->jumlah_mk_fakultas_kurang);
        $sheet->setCellValue('M' . $row, $mhs->total_sks_tidak_lulus);
        $this->styleDataRow($sheet, $row, $colCount, $isAlt);
    }

    public function exportNilaiSummary($filters = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Total Mahasiswa', 'Punya Nilai D', 'Punya Nilai E', 'MK Nas. Kurang', 'MK Fak. Kurang'];
        $this->writeTitleBlock($sheet, 'SUMMARY NILAI MAHASISWA', 'Ringkasan Statistik Kelulusan', $this->buildFilterDescription($filters), count($headers));
        
        $startRow = 6;
        $this->writeHeaderRow($sheet, $startRow, $headers); $startRow++;
        $stats = $this->getSummaryStats($filters);
        
        $sheet->setCellValue('A' . $startRow, $stats['total_mahasiswa']);
        $sheet->setCellValue('B' . $startRow, $stats['mahasiswa_dengan_nilai_d']);
        $sheet->setCellValue('C' . $startRow, $stats['mahasiswa_dengan_nilai_e']);
        $sheet->setCellValue('D' . $startRow, $stats['mk_nasional_belum_lulus']);
        $sheet->setCellValue('E' . $startRow, $stats['mk_fakultas_belum_lulus']);
        $this->styleDataRow($sheet, $startRow, count($headers));
        
        $this->autoSizeColumns($sheet, count($headers));
        $this->saveFile($spreadsheet, 'Dekan_Nilai_Summary_' . date('Y-m-d'));
    }

    private function enrichMahasiswaNilai($mhs, $mandatoryMKsByCategory)
    {
        $latestKhs = DB::table('khs_krs_mahasiswa as khs1')
            ->join('mata_kuliahs', 'khs1.matakuliah_id', '=', 'mata_kuliahs.id')
            ->whereIn('khs1.id', function($query) use ($mhs) {
                $query->select(DB::raw('MAX(id)'))->from('khs_krs_mahasiswa')->where('mahasiswa_id', $mhs->mahasiswa_id)->groupBy('matakuliah_id');
            })
            ->where('khs1.mahasiswa_id', $mhs->mahasiswa_id)
            ->select('mata_kuliahs.id', 'mata_kuliahs.kode', 'mata_kuliahs.name as nama', 'mata_kuliahs.sks', 'khs1.nilai_akhir_huruf')
            ->get();

        $nD = $latestKhs->where('nilai_akhir_huruf', 'D');
        $mhs->mata_kuliah_nilai_d = $nD->toArray();
        $mhs->jumlah_nilai_d = $nD->count();
        $mhs->total_sks_nilai_d = $nD->sum('sks');

        $nE = $latestKhs->where('nilai_akhir_huruf', 'E');
        $mhs->mata_kuliah_nilai_e = $nE->toArray();
        $mhs->jumlah_nilai_e = $nE->count();
        $mhs->total_sks_nilai_e = $nE->sum('sks');

        $mhs->jumlah_mk_nasional_kurang = ($mhs->mk_nasional === 'no') ? 1 : 0; // Simplified for report
        $mhs->jumlah_mk_fakultas_kurang = ($mhs->mk_fakultas === 'no') ? 1 : 0;
        $mhs->total_sks_tidak_lulus = $mhs->total_sks_nilai_d + $mhs->total_sks_nilai_e;

        return $mhs;
    }

    private function getMandatoryMKs($filters) { return DB::table('mata_kuliahs')->whereIn('tipe_mk', ['nasional', 'fakultas'])->get()->groupBy('tipe_mk'); }

    private function getMahasiswas($filters, $single)
    {
        $q = AkademikMahasiswa::join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('prodis', 'mahasiswa.prodi_id', '=', 'prodis.id')
            ->leftJoin('users', 'mahasiswa.user_id', '=', 'users.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select('prodis.nama as nama_prodi', 'mahasiswa.id as mahasiswa_id', 'mahasiswa.nim', 'users.name as nama_lengkap', 'akademik_mahasiswa.ipk', 'akademik_mahasiswa.sks_lulus', 'akademik_mahasiswa.mk_nasional', 'akademik_mahasiswa.mk_fakultas');
        if (!empty($filters['prodi_id'])) $q->where('mahasiswa.prodi_id', $filters['prodi_id']);
        if (!empty($filters['tahun_masuk'])) $q->where('akademik_mahasiswa.tahun_masuk', $filters['tahun_masuk']);
        if (!empty($filters['mahasiswa_id'])) $q->where('mahasiswa.id', $filters['mahasiswa_id']);
        return $single ? $q->limit(1)->get() : $q->orderBy('mahasiswa.nim')->get();
    }

    private function getSummaryStats($f)
    {
        $q = AkademikMahasiswa::join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->select(DB::raw('COUNT(*) as total_mahasiswa'), DB::raw('SUM(CASE WHEN nilai_d_melebihi_batas="yes" THEN 1 ELSE 0 END) as mahasiswa_dengan_nilai_d'), DB::raw('SUM(CASE WHEN nilai_e="yes" THEN 1 ELSE 0 END) as mahasiswa_dengan_nilai_e'), DB::raw('SUM(CASE WHEN mk_nasional="no" THEN 1 ELSE 0 END) as mk_nasional_belum_lulus'), DB::raw('SUM(CASE WHEN mk_fakultas="no" THEN 1 ELSE 0 END) as mk_fakultas_belum_lulus'));
        if (!empty($f['prodi_id'])) $q->where('mahasiswa.prodi_id', $f['prodi_id']);
        if (!empty($f['tahun_masuk'])) $q->where('akademik_mahasiswa.tahun_masuk', $f['tahun_masuk']);
        return (array)$q->first()->toArray();
    }

    private function buildFilterDescription($f)
    {
        $d = [];
        if (!empty($f['prodi_id'])) $d[] = 'Prodi: ' . (Prodi::find($f['prodi_id'])->nama ?? $f['prodi_id']);
        if (!empty($f['tahun_masuk'])) $d[] = 'Angkatan: ' . $f['tahun_masuk'];
        return empty($d) ? 'Semua Data' : implode(' | ', $d);
    }
}
