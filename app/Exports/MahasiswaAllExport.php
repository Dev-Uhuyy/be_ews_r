<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MahasiswaAllExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
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
        return [
            $row->nim,
            $row->nama_lengkap,
            $row->status_mahasiswa ?? '-',
            $row->nama_dosen_wali,
            $row->semester_aktif ?? '-',
            $row->tahun_masuk ?? '-',
            $row->ipk ?? 0,
            $row->sks_lulus ?? 0,
            $row->sks_tempuh ?? 0,
            $row->mk_nasional ?? '-',
            $row->mk_fakultas ?? '-',
            $row->mk_prodi ?? '-',
            $row->nilai_d_melebihi_batas ?? '-',
            $row->nilai_e ?? '-',
            $row->status_ews ?? '-',
            $row->status_kelulusan ?? '-'
        ];
    }
}
