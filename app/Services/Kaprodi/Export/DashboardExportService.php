<?php

namespace App\Services\Kaprodi\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Prodi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DashboardExportService
{
    private function getProdiId()
    {
        return Auth::user()->prodi_id;
    }

    /**
     * Export Kaprodi Dashboard to XLSX
     *
     * @param array $filters Optional: tahun_masuk (filter per tahun angkatan)
     */
    public function exportDashboard($filters = [])
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);
        $tahunMasuk = $filters['tahun_masuk'] ?? null;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN DASHBOARD KAPRODI');
        $sheet->setCellValue('A2', $prodi->kode_prodi . ' - ' . $prodi->nama);
        if ($tahunMasuk) {
            $sheet->setCellValue('A3', 'Filter Tahun Masuk: ' . $tahunMasuk);
        }
        $sheet->setCellValue('A4', 'Dicetak: ' . date('d-m-Y H:i'));

        $startRow = 6;

        // Get Data - apply tahun_masuk filter
        $statistikGlobal = $this->getStatistikGlobal($prodiId, $tahunMasuk);
        $rataIpkPerTahun = $this->getRataIpkPerTahun($prodiId, $tahunMasuk);
        $statistikKelulusan = $this->getStatistikKelulusan($prodiId);
        $tabelRingkasanProdi = $this->getTabelRingkasanProdi($prodiId, $tahunMasuk);

        // === STATISTIK GLOBAL ===
        $sheet->setCellValue('A' . $startRow, 'STATISTIK GLOBAL');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $globalHeaders = ['Total Mahasiswa', 'Aktif', 'Mangkir', 'Cuti', 'DO'];
        foreach ($globalHeaders as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($globalHeaders));

        $startRow++;
        $sheet->setCellValue('A' . $startRow, $statistikGlobal['total_mahasiswa']);
        $sheet->setCellValue('B' . $startRow, $statistikGlobal['total_mahasiswa_aktif']);
        $sheet->setCellValue('C' . $startRow, $statistikGlobal['total_mahasiswa_mangkir']);
        $sheet->setCellValue('D' . $startRow, $statistikGlobal['total_mahasiswa_cuti']);
        $sheet->setCellValue('E' . $startRow, $statistikGlobal['total_mahasiswa_do']);

        // === RATA IPK PER TAHUN ===
        $startRow += 3;
        $sheet->setCellValue('A' . $startRow, 'RATA-RATA IPK PER TAHUN');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $ipkHeaders = ['Tahun Masuk', 'Rata IPK', 'Jumlah Mahasiswa'];
        foreach ($ipkHeaders as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($ipkHeaders));

        $startRow++;
        foreach ($rataIpkPerTahun as $row) {
            $sheet->setCellValue('A' . $startRow, $row->tahun_masuk);
            $sheet->setCellValue('B' . $startRow, $row->rata_ipk);
            $sheet->setCellValue('C' . $startRow, $row->jumlah_mahasiswa);
            $startRow++;
        }

        // === STATISTIK KELULUSAN ===
        $startRow += 2;
        $sheet->setCellValue('A' . $startRow, 'STATISTIK KELULUSAN');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $kelulusanHeaders = ['Eligible', 'Non-Eligible'];
        foreach ($kelulusanHeaders as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($kelulusanHeaders));

        $startRow++;
        $sheet->setCellValue('A' . $startRow, $statistikKelulusan['eligible']);
        $sheet->setCellValue('B' . $startRow, $statistikKelulusan['non_eligible']);

        // === RINGKASAN PRODI ===
        $startRow += 3;
        $sheet->setCellValue('A' . $startRow, 'RINGKASAN PROGRAM STUDI');
        $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
        $startRow++;

        $prodiHeaders = ['Kode Prodi', 'Nama Prodi', 'Jumlah Mhs', 'Aktif', 'Cuti', 'Mangkir', 'IPK Rata', 'Tepat Waktu', 'Perhatian', 'Kritis'];
        foreach ($prodiHeaders as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($prodiHeaders));

        $startRow++;
        foreach ($tabelRingkasanProdi as $row) {
            $sheet->setCellValue('A' . $startRow, $row['prodi']['kode_prodi']);
            $sheet->setCellValue('B' . $startRow, $row['prodi']['nama_prodi']);
            $sheet->setCellValue('C' . $startRow, $row['jumlah_mahasiswa']);
            $sheet->setCellValue('D' . $startRow, $row['jumlah_mahasiswa_aktif']);
            $sheet->setCellValue('E' . $startRow, $row['jumlah_mahasiswa_cuti']);
            $sheet->setCellValue('F' . $startRow, $row['jumlah_mahasiswa_mangkir']);
            $sheet->setCellValue('G' . $startRow, $row['ipk_rata_rata']);
            $sheet->setCellValue('H' . $startRow, $row['jumlah_tepat_waktu']);
            $sheet->setCellValue('I' . $startRow, $row['jumlah_perhatian']);
            $sheet->setCellValue('J' . $startRow, $row['jumlah_kritis']);
            $startRow++;
        }

        // Auto size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->saveFile($spreadsheet, 'Kaprodi_Dashboard_' . $prodi->kode_prodi . '_' . date('Y-m-d'));
    }

    /**
     * Export Dashboard Detail to XLSX
     */
    /**
     * Export Dashboard Detail to XLSX
     *
     * @param array $filters Optional: tahun_masuk (filter per tahun angkatan)
     */
    public function exportDashboardDetail($filters = [])
    {
        $prodiId = $this->getProdiId();
        $prodi = Prodi::find($prodiId);
        $tahunMasuk = $filters['tahun_masuk'] ?? null;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'LAPORAN DETAIL DASHBOARD KAPRODI');
        $sheet->setCellValue('A2', $prodi->kode_prodi . ' - ' . $prodi->nama);
        if ($tahunMasuk) {
            $sheet->setCellValue('A3', 'Filter Tahun Masuk: ' . $tahunMasuk);
        } else {
            $sheet->setCellValue('A3', 'Per Tahun Angkatan');
        }
        $sheet->setCellValue('A4', 'Dicetak: ' . date('d-m-Y H:i'));

        $startRow = 6;

        $tahunData = $this->getTahunData($prodiId, $tahunMasuk);

        $headers = ['Tahun Masuk', 'Jumlah Mhs', 'Aktif', 'Cuti 2x', 'IPK Rata', 'Tepat Waktu', 'Normal', 'Perhatian', 'Kritis'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, $startRow, $header);
        }
        $this->styleHeader($sheet, $startRow, count($headers));

        $startRow++;
        foreach ($tahunData as $row) {
            $sheet->setCellValue('A' . $startRow, $row->tahun_masuk);
            $sheet->setCellValue('B' . $startRow, $row->jumlah_mahasiswa);
            $sheet->setCellValue('C' . $startRow, $row->mahasiswa_aktif);
            $sheet->setCellValue('D' . $startRow, $row->jumlah_cuti_2x);
            $sheet->setCellValue('E' . $startRow, $row->ipk_rata_rata);
            $sheet->setCellValue('F' . $startRow, $row->tepat_waktu);
            $sheet->setCellValue('G' . $startRow, $row->normal);
            $sheet->setCellValue('H' . $startRow, $row->perhatian);
            $sheet->setCellValue('I' . $startRow, $row->kritis);
            $startRow++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->saveFile($spreadsheet, 'Kaprodi_Dashboard_Detail_' . $prodi->kode_prodi . '_' . date('Y-m-d'));
    }

    private function getStatistikGlobal($prodiId, $tahunMasuk = null)
    {
        $query = Mahasiswa::where('prodi_id', $prodiId)
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->whereHas('akademik', function ($q) use ($tahunMasuk) {
                $q->where('tahun_masuk', $tahunMasuk);
            });
        }

        $totalMahasiswa = $query->count();

        $statusQuery = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->where('prodi_id', $prodiId)
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $statusQuery->whereHas('akademik', function ($q) use ($tahunMasuk) {
                $q->where('tahun_masuk', $tahunMasuk);
            });
        }

        $statusBreakdown = $statusQuery->groupBy('status_mahasiswa')->get()->keyBy('status_mahasiswa');

        return [
            'total_mahasiswa' => $totalMahasiswa,
            'total_mahasiswa_aktif' => ($statusBreakdown->get('aktif')->jumlah ?? 0) + ($statusBreakdown->get('Aktif')->jumlah ?? 0),
            'total_mahasiswa_mangkir' => ($statusBreakdown->get('mangkir')->jumlah ?? 0) + ($statusBreakdown->get('Mangkir')->jumlah ?? 0),
            'total_mahasiswa_cuti' => ($statusBreakdown->get('cuti')->jumlah ?? 0) + ($statusBreakdown->get('Cuti')->jumlah ?? 0),
            'total_mahasiswa_do' => Mahasiswa::where('prodi_id', $prodiId)->whereRaw('LOWER(status_mahasiswa) = "do"')->count(),
        ];
    }

    private function getRataIpkPerTahun($prodiId, $tahunMasuk = null)
    {
        $query = AkademikMahasiswa::select('tahun_masuk', DB::raw('ROUND(AVG(ipk), 2) as rata_ipk'), DB::raw('COUNT(*) as jumlah_mahasiswa'))
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->whereNotNull('tahun_masuk')
            ->whereNotNull('ipk')
            ->where('ipk', '>', 0)
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        return $query->groupBy('tahun_masuk')->orderBy('tahun_masuk', 'desc')->get();
    }

    private function getStatistikKelulusan($prodiId)
    {
        $eligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->where('early_warning_system.status_kelulusan', 'eligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('mahasiswa.prodi_id', $prodiId)
            ->where('early_warning_system.status_kelulusan', 'noneligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        return ['eligible' => $eligible, 'non_eligible' => $noneligible];
    }

    private function getTabelRingkasanProdi($prodiId, $tahunMasuk = null)
    {
        $prodi = Prodi::find($prodiId);

        $query = AkademikMahasiswa::select(
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as jumlah_mahasiswa_aktif'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "cuti" THEN 1 ELSE 0 END) as jumlah_mahasiswa_cuti'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "mangkir" THEN 1 ELSE 0 END) as jumlah_mahasiswa_mangkir'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as jumlah_tepat_waktu'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as jumlah_perhatian'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as jumlah_kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        $stats = $query->first();

        return [[
            'prodi' => ['kode_prodi' => $prodi->kode_prodi, 'nama_prodi' => $prodi->nama],
            'jumlah_mahasiswa' => $stats->jumlah_mahasiswa ?? 0,
            'jumlah_mahasiswa_aktif' => $stats->jumlah_mahasiswa_aktif ?? 0,
            'jumlah_mahasiswa_cuti' => $stats->jumlah_mahasiswa_cuti ?? 0,
            'jumlah_mahasiswa_mangkir' => $stats->jumlah_mahasiswa_mangkir ?? 0,
            'ipk_rata_rata' => $stats->ipk_rata_rata ?? 0,
            'jumlah_tepat_waktu' => $stats->jumlah_tepat_waktu ?? 0,
            'jumlah_perhatian' => $stats->jumlah_perhatian ?? 0,
            'jumlah_kritis' => $stats->jumlah_kritis ?? 0,
        ]];
    }

    private function getTahunData($prodiId, $tahunMasuk = null)
    {
        $query = AkademikMahasiswa::select(
                    'akademik_mahasiswa.tahun_masuk',
                    DB::raw('COUNT(DISTINCT akademik_mahasiswa.id) as jumlah_mahasiswa'),
                    DB::raw('SUM(CASE WHEN LOWER(mahasiswa.status_mahasiswa) = "aktif" THEN 1 ELSE 0 END) as mahasiswa_aktif'),
                    DB::raw('SUM(CASE WHEN mahasiswa.cuti_2 = "yes" THEN 1 ELSE 0 END) as jumlah_cuti_2x'),
                    DB::raw('ROUND(AVG(akademik_mahasiswa.ipk), 2) as ipk_rata_rata'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "tepat_waktu" THEN 1 ELSE 0 END) as tepat_waktu'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "normal" THEN 1 ELSE 0 END) as normal'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "perhatian" THEN 1 ELSE 0 END) as perhatian'),
                    DB::raw('SUM(CASE WHEN early_warning_system.status = "kritis" THEN 1 ELSE 0 END) as kritis')
                )
                ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
                ->leftJoin('early_warning_system', 'akademik_mahasiswa.id', '=', 'early_warning_system.akademik_mahasiswa_id')
                ->where('mahasiswa.prodi_id', $prodiId)
                ->whereNotNull('akademik_mahasiswa.tahun_masuk')
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")');

        if ($tahunMasuk) {
            $query->where('akademik_mahasiswa.tahun_masuk', $tahunMasuk);
        }

        return $query->groupBy('akademik_mahasiswa.tahun_masuk')
            ->orderBy('akademik_mahasiswa.tahun_masuk', 'desc')
            ->get();
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
