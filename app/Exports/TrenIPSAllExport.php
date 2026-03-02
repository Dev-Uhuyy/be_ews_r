<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrenIPSAllExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
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
            'Tren IPS',
            'Jumlah MK Gagal (E)',
            'Jumlah Mahasiswa Mengulang'
        ];
    }

    public function map($row): array
    {
        $row = (object) $row;
        return [
            $row->tahun_masuk,
            $row->jumlah_mahasiswa,
            ucfirst($row->tren_ips),
            $row->mk_gagal,
            $row->mk_ulang
        ];
    }
}
