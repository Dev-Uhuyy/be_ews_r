<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TindakLanjutExport implements FromCollection, WithHeadings, WithMapping
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
            'Kategori',
            'Tanggal Pengajuan',
            'Dosen Wali',
            'Status',
            'Link Berkas',
            'Catatan Mahasiswa'
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->nama,
            $row->nim,
            ucwords(str_replace('_', ' ', $row->kategori)),
            $row->tanggal_pengajuan,
            $row->dosen_wali,
            ucwords(str_replace('_', ' ', $row->status_tindak_lanjut)),
            $row->link,
            $row->catatan
        ];
    }
}
