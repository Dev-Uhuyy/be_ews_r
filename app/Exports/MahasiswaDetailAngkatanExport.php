<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MahasiswaDetailAngkatanExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
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
        return [
            $row->nim,
            $row->nama_lengkap,
            $row->nama_dosen_wali,
            $row->ipk,
            $row->sks_lulus,
            $row->mk_nasional,
            implode(', ', $row->mk_nasional_detail ?? []),
            $row->mk_fakultas,
            implode(', ', $row->mk_fakultas_detail ?? []),
            $row->mk_prodi,
            implode(', ', $row->mk_prodi_detail ?? []),
            $row->nilai_e,
            implode(', ', $row->nilai_e_detail ?? []),
            $row->jumlah_nilai_d,
            implode(', ', $row->nilai_d_detail ?? []),
            $row->status_ews,
            $row->status_kelulusan
        ];
    }
}
