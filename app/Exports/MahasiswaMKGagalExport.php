<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MahasiswaMKGagalExport extends BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data, string $reportTitle = 'Daftar Mata Kuliah Gagal', array $additionalInfo = [])
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
            'Nama Mahasiswa',
            'NIM',
            'Nama Mata Kuliah',
            'Kode Mata Kuliah',
            'Dosen Pengampu',
            'Presensi (%)'
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;
        return [
            $no,
            $this->sanitizeForExcel($row->nama ?? '-'),
            $this->sanitizeForExcel($row->nim ?? '-'),
            $this->sanitizeForExcel($row->nama_matkul ?? '-'),
            $this->sanitizeForExcel($row->kode_matkul ?? '-'),
            $this->sanitizeForExcel($row->dosen_pengampu ?? '-'),
            number_format($row->presensi ?? 0, 2)
        ];
    }
}