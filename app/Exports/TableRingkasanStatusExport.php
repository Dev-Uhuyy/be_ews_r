<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TableRingkasanStatusExport implements FromCollection, WithHeadings, WithMapping
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
            'IPK < 2.0',
            'Mangkir',
            'Cuti',
            'Perhatian'
        ];
    }

    public function map($row): array
    {
        return [
            $row->tahun_masuk,
            $row->jumlah_mahasiswa,
            $row->ipk_kurang_dari_2,
            $row->mangkir,
            $row->cuti,
            $row->perhatian
        ];
    }
}
