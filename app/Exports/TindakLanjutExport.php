<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TindakLanjutExport extends BaseExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data, string $reportTitle = 'Daftar Tindak Lanjut', array $additionalInfo = [])
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
            'ID Surat',
            'Nama',
            'NIM',
            'Kategori',
            'Tanggal Pengajuan',
            'Dosen Wali',
            'Status',
            'Link Berkas'
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;
        return [
            $no,
            $row->id ?? '-',
            $this->sanitizeForExcel($row->nama ?? '-'),
            $row->nim ?? '-',
            $this->sanitizeForExcel($this->formatKategori($row->kategori ?? '-')),
            $row->tanggal_pengajuan ?? '-',
            $this->sanitizeForExcel($row->dosen_wali ?? '-'),
            $this->sanitizeForExcel($this->formatStatus($row->status_tindak_lanjut ?? '-')),
            $row->link ?? '-'
        ];
    }

    private function formatKategori(string $kategori): string
    {
        $map = [
            'surat_rekomitmen' => 'Surat Rekomitmen',
            'surat_pernyataan' => 'Surat Pernyataan',
            'bimbingan_akademik' => 'Bimbingan Akademik',
            '的其他' => 'Lainnya',
        ];
        return $map[$kategori] ?? ucwords(str_replace('_', ' ', $kategori));
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
        return $map[$statusLower] ?? ucwords(str_replace('_', ' ', $status));
    }
}