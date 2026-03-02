<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SuratRekomitmenExport implements FromCollection, WithHeadings, WithMapping
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
            'ID Tiket',
            'Nama',
            'NIM',
            'Tanggal Pengajuan',
            'Dosen Wali',
            'Status Tindak Lanjut',
            'Link Rekomitmen'
        ];
    }

    public function map($row): array
    {
        return [
            $row->id_tiket,
            $row->nama,
            $row->nim,
            $row->tanggal_pengajuan,
            $row->dosen_wali,
            $row->status_tindak_lanjut,
            $row->link_rekomitmen
        ];
    }
}
