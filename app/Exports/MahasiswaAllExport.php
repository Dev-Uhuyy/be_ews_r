<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MahasiswaAllExport extends BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data, string $reportTitle = 'Data Semua Mahasiswa', array $additionalInfo = [])
    {
        parent::__construct($reportTitle, $additionalInfo);
        $this->data = $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No',
            'NIM',
            'Nama Lengkap',
            'Status Mahasiswa',
            'Dosen Wali',
            'Semester Aktif',
            'Tahun Masuk',
            'IPK',
            'SKS Lulus',
            'SKS Tempuh',
            'MK Nasional',
            'MK Fakultas',
            'MK Prodi',
            'Nilai D Melebihi Batas',
            'Nilai E',
            'Status EWS',
            'Status Kelulusan'
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;
        return [
            $no,
            $row->nim,
            $this->sanitizeForExcel($row->nama_lengkap),
            $this->sanitizeForExcel(ucfirst($row->status_mahasiswa ?? '-')),
            $this->sanitizeForExcel($row->nama_dosen_wali ?? '-'),
            $row->semester_aktif ?? '-',
            $row->tahun_masuk ?? '-',
            number_format($row->ipk ?? 0, 2),
            $row->sks_lulus ?? 0,
            $row->sks_tempuh ?? 0,
            $row->mk_nasional ?? '-',
            $row->mk_fakultas ?? '-',
            $row->mk_prodi ?? '-',
            $row->nilai_d_melebihi_batas ?? '-',
            $row->nilai_e ?? '-',
            $this->sanitizeForExcel($this->formatStatusEws($row->status_ews ?? '-')),
            $this->sanitizeForExcel($this->formatStatusKelulusan($row->status_kelulusan ?? '-'))
        ];
    }

    private function formatStatusEws(string $status): string
    {
        $map = [
            'tepat_waktu' => 'Tepat Waktu',
            'normal' => 'Normal',
            'perhatian' => 'Perhatian',
            'kritis' => 'Kritis',
        ];
        return $map[$status] ?? ucfirst($status);
    }

    private function formatStatusKelulusan(string $status): string
    {
        $map = [
            'eligible' => 'Eligible',
            'noneligible' => 'Non-Eligible',
        ];
        return $map[$status] ?? ucfirst($status);
    }
}