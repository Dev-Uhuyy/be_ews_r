<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SuratRekomitmenExport extends BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data, string $reportTitle = 'Surat Rekomitmen', array $additionalInfo = [])
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
        static $no = 0;
        $no++;
        return [
            $no,
            $row->id_tiket ?? '-',
            $this->sanitizeForExcel($row->nama ?? '-'),
            $row->nim ?? '-',
            $row->tanggal_pengajuan ?? '-',
            $this->sanitizeForExcel($row->dosen_wali ?? '-'),
            $this->sanitizeForExcel($this->formatStatus($row->status_tindak_lanjut ?? '-')),
            $row->link_rekomitmen ?? '-'
        ];
    }

    private function formatStatus(string $status): string
    {
        $map = [
            'menunggu' => 'Menunggu',
            'diproses' => 'Diproses',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'selesai' => 'Selesai',
        ];
        $statusLower = strtolower($status);
        return $map[$statusLower] ?? ucfirst($status);
    }
}