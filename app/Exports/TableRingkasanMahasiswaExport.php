<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TableRingkasanMahasiswaExport extends BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data, string $reportTitle = 'Ringkasan Data Mahasiswa per Angkatan', array $additionalInfo = [])
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
        static $no = 0;
        $no++;
        return [
            $no,
            $row->tahun_masuk ?? '-',
            $row->jumlah_mahasiswa ?? 0,
            $row->aktif ?? 0,
            $row->cuti ?? 0,
            $row->mangkir ?? 0,
            number_format($row->rata_ipk ?? 0, 2),
            $row->tepat_waktu ?? 0,
            $row->normal ?? 0,
            $row->perhatian ?? 0,
            $row->kritis ?? 0
        ];
    }
}