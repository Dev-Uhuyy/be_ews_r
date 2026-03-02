<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MahasiswaMKGagalExport implements FromCollection, WithHeadings, WithMapping
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
            'Nama Mahasiswa',
            'NIM',
            'Nama Mata Kuliah',
            'Kode Mata Kuliah',
            'Kelompok',
            'Presensi (%)'
        ];
    }

    public function map($row): array
    {
        return [
            $row->nama,
            $row->nim,
            $row->nama_matkul,
            $row->kode_matkul,
            $row->kode_kelompok,
            $row->presensi ?? 0
        ];
    }
}
