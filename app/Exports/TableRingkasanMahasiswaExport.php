<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TableRingkasanMahasiswaExport implements FromCollection, WithHeadings, WithMapping
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
            'Angkatan',
            'Jumlah Mahasiswa',
            'Aktif',
            'Cuti',
            'Mangkir',
            'Rata-rata IPK',
            'Tepat Waktu',
            'Normal',
            'Perhatian',
            'Kritis'
        ];
    }

    public function map($row): array
    {
        return [
            $row->tahun_masuk,
            $row->jumlah_mahasiswa,
            $row->aktif,
            $row->cuti,
            $row->mangkir,
            $row->rata_ipk,
            $row->tepat_waktu,
            $row->normal,
            $row->perhatian,
            $row->kritis
        ];
    }
}
