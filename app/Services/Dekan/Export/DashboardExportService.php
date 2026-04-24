<?php

namespace App\Services\Dekan\Export;

use App\Models\AkademikMahasiswa;
use App\Models\Mahasiswa;
use App\Models\EarlyWarningSystem;
use App\Models\Prodi;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;

class DashboardExportService
{
    /**
     * Export Dekan Dashboard to XLSX
     */
    public function exportDashboard()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->setCellValue('A1', 'LAPORAN DASHBOARD DEKAN');
        $sheet->setCellValue('A2', 'Semua Program Studi');
        $sheet->setCellValue('A3', 'Dicetak: ' . date('d-m-Y H:i'));

        // Get Data
        $statistikGlobal = $this->getStatistikGlobal();
        $rataIpkPerTahun = $this->getRataIpkPerTahun();
        $statistikKelulusan = $this->getStatistikKelulusan();
        $tabelRingkasanProdi = $this->getTabelRingkasanProdi();

        $startRow = 5;

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

        // === TABEL RINGKASAN PER PRODI ===
        $startRow += 3;
        $sheet->setCellValue('A' . $startRow, 'RINGKASAN PER PROGRAM STUDI');
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

        $this->saveFile($spreadsheet, 'Dekan_Dashboard_' . date('Y-m-d'));
    }

    /**
     * Export Dashboard Detail to XLSX
     *
     * @param array $filters Optional filters: prodi_id (filter ke satu prodi)
     */
    public function exportDashboardDetail($filters = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $prodiId = $filters['prodi_id'] ?? null;
        $prodiNama = $prodiId ? Prodi::find($prodiId)->nama : 'Semua Prodi';

        $sheet->setCellValue('A1', 'LAPORAN DETAIL DASHBOARD DEKAN');
        $sheet->setCellValue('A2', 'Program Studi: ' . $prodiNama);
        $sheet->setCellValue('A3', 'Per Prodi dan Tahun Angkatan');
        $sheet->setCellValue('A4', 'Dicetak: ' . date('d-m-Y H:i'));

        $prodis = $prodiId ? Prodi::where('id', $prodiId)->get() : Prodi::all();
        $startRow = 5;

        foreach ($prodis as $prodi) {
            $tahunData = $this->getDataPerProdi($prodi->id);

            $sheet->setCellValue('A' . $startRow, $prodi->kode_prodi . ' - ' . $prodi->nama);
            $sheet->getStyle('A' . $startRow)->applyFromArray(['font' => ['bold' => true]]);
            $startRow++;

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

            $startRow += 2;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->saveFile($spreadsheet, 'Dekan_Dashboard_Detail_' . date('Y-m-d'));
    }

    private function getStatistikGlobal()
    {
        $query = Mahasiswa::whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")');
        $totalMahasiswa = $query->count();

        $statusBreakdown = Mahasiswa::select('status_mahasiswa', DB::raw('COUNT(*) as jumlah'))
            ->whereRaw('LOWER(status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('status_mahasiswa')
            ->get()
            ->keyBy('status_mahasiswa');

        return [
            'total_mahasiswa' => $totalMahasiswa,
            'total_mahasiswa_aktif' => ($statusBreakdown->get('aktif')->jumlah ?? 0) + ($statusBreakdown->get('Aktif')->jumlah ?? 0),
            'total_mahasiswa_mangkir' => ($statusBreakdown->get('mangkir')->jumlah ?? 0) + ($statusBreakdown->get('Mangkir')->jumlah ?? 0),
            'total_mahasiswa_cuti' => ($statusBreakdown->get('cuti')->jumlah ?? 0) + ($statusBreakdown->get('Cuti')->jumlah ?? 0),
            'total_mahasiswa_do' => Mahasiswa::whereRaw('LOWER(status_mahasiswa) = "do"')->count(),
        ];
    }

    private function getRataIpkPerTahun()
    {
        return AkademikMahasiswa::select(
                'tahun_masuk',
                DB::raw('ROUND(AVG(ipk), 2) as rata_ipk'),
                DB::raw('COUNT(*) as jumlah_mahasiswa')
            )
            ->whereNotNull('tahun_masuk')
            ->whereNotNull('ipk')
            ->where('ipk', '>', 0)
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->groupBy('tahun_masuk')
            ->orderBy('tahun_masuk', 'desc')
            ->get();
    }

    private function getStatistikKelulusan()
    {
        $eligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'eligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        $noneligible = EarlyWarningSystem::join('akademik_mahasiswa', 'early_warning_system.akademik_mahasiswa_id', '=', 'akademik_mahasiswa.id')
            ->join('mahasiswa', 'akademik_mahasiswa.mahasiswa_id', '=', 'mahasiswa.id')
            ->where('early_warning_system.status_kelulusan', 'noneligible')
            ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
            ->count();

        return [
            'eligible' => $eligible,
            'non_eligible' => $noneligible,
        ];
    }

    private function getTabelRingkasanProdi()
    {
        $prodis = Prodi::all();
        $result = [];

        foreach ($prodis as $prodi) {
            $stats = AkademikMahasiswa::select(
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
                    ->where('mahasiswa.prodi_id', $prodi->id)
                    ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                    ->first();

            $result[] = [
                'prodi' => [
                    'kode_prodi' => $prodi->kode_prodi,
                    'nama_prodi' => $prodi->nama,
                ],
                'jumlah_mahasiswa' => $stats->jumlah_mahasiswa ?? 0,
                'jumlah_mahasiswa_aktif' => $stats->jumlah_mahasiswa_aktif ?? 0,
                'jumlah_mahasiswa_cuti' => $stats->jumlah_mahasiswa_cuti ?? 0,
                'jumlah_mahasiswa_mangkir' => $stats->jumlah_mahasiswa_mangkir ?? 0,
                'ipk_rata_rata' => $stats->ipk_rata_rata ?? 0,
                'jumlah_tepat_waktu' => $stats->jumlah_tepat_waktu ?? 0,
                'jumlah_perhatian' => $stats->jumlah_perhatian ?? 0,
                'jumlah_kritis' => $stats->jumlah_kritis ?? 0,
            ];
        }

        return $result;
    }

    private function getDataPerProdi($prodiId)
    {
        return AkademikMahasiswa::select(
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
                ->whereRaw('LOWER(mahasiswa.status_mahasiswa) NOT IN ("lulus", "do")')
                ->groupBy('akademik_mahasiswa.tahun_masuk')
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
