<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class BaseExport implements WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    protected $reportTitle;
    protected $additionalInfo = [];

    public function __construct(string $reportTitle = '', array $additionalInfo = [])
    {
        $this->reportTitle = $reportTitle;
        $this->additionalInfo = $additionalInfo;
        
        // Force UTF-8 for PhpSpreadsheet to handle Indonesian characters properly
        StringHelper::setDecimalSeparator('.');
        StringHelper::setThousandsSeparator(',');
    }

    /**
     * Sanitize value for proper Excel encoding
     */
    protected function sanitizeForExcel($value)
    {
        if ($value === null) {
            return null;
        }
        
        if (is_string($value)) {
            // Convert to proper UTF-8, replacing invalid sequences
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            // Use iconv to clean any remaining invalid characters
            $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value;
        }
        
        return $value;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->reportTitle;
    }

    /**
     * Register events for adding header rows
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                // Insert 3 rows at the top for FIK header, subtitle, and column info
                $sheet->insertNewRowBefore(1, 3);

                // Row 1: TOP FIK - Faculty Header
                $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 16,
                        'bold' => true,
                        'color' => ['rgb' => '1F4E79'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D6DCE5'],
                    ],
                ]);
                $sheet->mergeCells('A1:' . $highestColumn . '1');
                $sheet->setCellValue('A1', 'FAKULTAS ILMU KOMPUTER');
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Row 2: Report Title
                $sheet->getStyle('A2:' . $highestColumn . '2')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 13,
                        'bold' => true,
                        'color' => ['rgb' => '2E75B6'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->mergeCells('A2:' . $highestColumn . '2');
                $sheet->setCellValue('A2', strtoupper($this->reportTitle));
                $sheet->getRowDimension(2)->setRowHeight(25);

                // Row 3: Additional info (tanggal, etc) - Indonesian format
                $tanggal = 'Dicetak pada: ' . date('d F Y, H:i') . ' WIB';
                if (!empty($this->additionalInfo)) {
                    $info = implode(' | ', $this->additionalInfo);
                    $tanggal = $info . ' | ' . $tanggal;
                }
                $sheet->getStyle('A3:' . $highestColumn . '3')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 10,
                        'italic' => true,
                        'color' => ['rgb' => '595959'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->mergeCells('A3:' . $highestColumn . '3');
                $sheet->setCellValue('A3', $tanggal);
                $sheet->getRowDimension(3)->setRowHeight(20);

                // Now style the header row (which is now row 4 after insertion)
                $headerRow = 4;
                $sheet->getStyle('A' . $headerRow . ':' . $highestColumn . $headerRow)->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 11,
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2E75B6'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'B4C6E7'],
                        ],
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(30);

                // Style data rows (starting from row 5)
                $dataStartRow = 5;
                if ($sheet->getHighestRow() >= $dataStartRow) {
                    $sheet->getStyle('A' . $dataStartRow . ':' . $highestColumn . $sheet->getHighestRow())->applyFromArray([
                        'font' => [
                            'name' => 'Arial',
                            'size' => 10,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'B4C6E7'],
                            ],
                        ],
                    ]);

                    // Alternate row coloring for readability
                    for ($i = $dataStartRow; $i <= $sheet->getHighestRow(); $i++) {
                        if ($i % 2 == 0) {
                            $sheet->getStyle('A' . $i . ':' . $highestColumn . $i)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F2F2F2'],
                                ],
                            ]);
                        }
                    }
                }

                // Set print area and page setup for professional output
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
            },
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Styles are applied in AfterSheet event
    }
}