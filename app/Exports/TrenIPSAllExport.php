<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrenIPSAllExport extends BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data, string $reportTitle = 'Tren IPS per Angkatan', array $additionalInfo = [])
    {
        parent::__construct($reportTitle, $additionalInfo);
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
            'No',
            'Angkatan',
            'Jumlah Mahasiswa',
            'Tren IPS',
            'Jumlah MK Gagal (E)',
            'Jumlah Mahasiswa Mengulang'
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;
        $row = (object) $row;
        return [
            $no,
            $row->tahun_masuk ?? '-',
            $row->jumlah_mahasiswa ?? 0,
            $this->sanitizeForExcel($this->formatTren($row->tren_ips ?? '-')),
            $row->mk_gagal ?? 0,
            $row->mk_ulang ?? 0
        ];
    }

    private function formatTren(string $tren): string
    {
        $map = [
            'naik' => 'Naik',
            'turun' => 'Turun',
            'stabil' => 'Stabil',
        ];
        return $map[strtolower($tren)] ?? ucfirst($tren);
    }
}