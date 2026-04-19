<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class KhsKrsExport implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Semester',
            'Kode MK',
            'Nama Mata Kuliah',
            'SKS',
            'Nilai Huruf',
            'Nilai Angka',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'KHS KRS';
    }
}
