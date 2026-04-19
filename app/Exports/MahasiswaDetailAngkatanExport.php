<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MahasiswaDetailAngkatanExport extends BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data, string $reportTitle = 'Detail Mahasiswa per Angkatan', array $additionalInfo = [])
    {
        parent::__construct($reportTitle, $additionalInfo);
        $this->data = $data;
    }

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
            'Dosen Wali',
            'IPK',
            'SKS Lulus',
            'MK Nasional (Aman)',
            'MK Nasional (Belum Lulus)',
            'MK Fakultas (Aman)',
            'MK Fakultas (Belum Lulus)',
            'MK Prodi (Aman)',
            'MK Prodi (Belum Lulus)',
            'Bebas Nilai E',
            'Mata Kuliah Nilai E',
            'Jumlah Nilai D',
            'Mata Kuliah Nilai D',
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
            $this->sanitizeForExcel($row->nama_dosen_wali ?? '-'),
            number_format($row->ipk ?? 0, 2),
            $row->sks_lulus ?? 0,
            $row->mk_nasional ?? '-',
            implode(', ', $row->mk_nasional_detail ?? []),
            $row->mk_fakultas ?? '-',
            implode(', ', $row->mk_fakultas_detail ?? []),
            $row->mk_prodi ?? '-',
            implode(', ', $row->mk_prodi_detail ?? []),
            $row->nilai_e == 0 ? 'Ya' : 'Tidak',
            implode(', ', $row->nilai_e_detail ?? []),
            $row->jumlah_nilai_d ?? 0,
            implode(', ', $row->nilai_d_detail ?? []),
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