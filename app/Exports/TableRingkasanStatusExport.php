<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TableRingkasanStatusExport extends BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data, string $reportTitle = 'Ringkasan Status Mahasiswa per Angkatan', array $additionalInfo = [])
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
            'IPK < 2.0',
            'Mangkir',
            'Cuti',
            'Perhatian'
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
            $row->ipk_kurang_dari_2 ?? 0,
            $row->mangkir ?? 0,
            $row->cuti ?? 0,
            $row->perhatian ?? 0
        ];
    }
}