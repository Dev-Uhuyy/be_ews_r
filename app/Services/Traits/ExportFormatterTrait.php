<?php

namespace App\Services\Traits;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

/**
 * Shared formatting utilities for all XLSX export services.
 *
 * Palette:
 *   - Title bg    : #2D3E50  (dark navy)  text: white
 *   - Header bg   : #3D8BCD  (blue)       text: white
 *   - Alt row     : #EAF4FB  (light blue)
 *   - Border      : #B0C4D8
 */
trait ExportFormatterTrait
{
    // ──────────────────────────────────────────────────────────────
    // Title Block (rows 1-4)
    // ──────────────────────────────────────────────────────────────

    /**
     * Write a standardised 4-row title block and apply styles.
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param string $title      Main title (row 1)
     * @param string $subtitle   Subtitle / scope (row 2)
     * @param string $filter     Filter description (row 3)  — pass '' to skip
     * @param int    $colSpan    Number of columns to merge for the title row
     */
    protected function writeTitleBlock($sheet, string $title, string $subtitle, string $filter = '', int $colSpan = 10): void
    {
        $endCol = $this->colLetter($colSpan);

        /* ── Row 1 — main title ── */
        $sheet->mergeCells("A1:{$endCol}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D3E50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        /* ── Row 2 — subtitle ── */
        $sheet->mergeCells("A2:{$endCol}2");
        $sheet->setCellValue('A2', $subtitle);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3D5A73']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        /* ── Row 3 — filter / scope ── */
        if ($filter !== '') {
            $sheet->mergeCells("A3:{$endCol}3");
            $sheet->setCellValue('A3', $filter);
            $sheet->getStyle('A3')->applyFromArray([
                'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '444444'], 'name' => 'Calibri'],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4F8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        /* ── Row 4 — print date ── */
        $sheet->mergeCells("A4:{$endCol}4");
        $sheet->setCellValue('A4', 'Dicetak: ' . date('d-m-Y H:i'));
        $sheet->getStyle('A4')->applyFromArray([
            'font'      => ['size' => 9, 'color' => ['rgb' => '888888'], 'name' => 'Calibri'],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Header Row
    // ──────────────────────────────────────────────────────────────

    /**
     * Write header row at $row using $headers array and apply blue style.
     */
    protected function writeHeaderRow($sheet, int $row, array $headers): void
    {
        foreach ($headers as $col => $label) {
            $sheet->setCellValueByColumnAndRow($col + 1, $row, $label);
        }

        $endCol = $this->colLetter(count($headers));
        $sheet->getStyle("A{$row}:{$endCol}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3D8BCD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0C4D8']],
            ],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);

        // Freeze panes below header row
        $sheet->freezePane('A' . ($row + 1));
    }

    // ──────────────────────────────────────────────────────────────
    // Data Row Styling
    // ──────────────────────────────────────────────────────────────

    /**
     * Apply alternating row coloring + thin border to a data row.
     */
    protected function styleDataRow($sheet, int $row, int $colCount, bool $isAlt = false): void
    {
        $endCol = $this->colLetter($colCount);
        $style  = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DCE6F1']],
            ],
            'font' => ['size' => 10, 'name' => 'Calibri'],
        ];
        if ($isAlt) {
            $style['fill'] = ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF4FB']];
        }
        $sheet->getStyle("A{$row}:{$endCol}{$row}")->applyFromArray($style);
    }

    // ──────────────────────────────────────────────────────────────
    // Section sub-header (between data groups)
    // ──────────────────────────────────────────────────────────────

    protected function writeSectionHeader($sheet, int $row, string $label, int $colSpan = 10): void
    {
        $endCol = $this->colLetter($colSpan);
        $sheet->mergeCells("A{$row}:{$endCol}{$row}");
        $sheet->setCellValue("A{$row}", $label);
        $sheet->getStyle("A{$row}:{$endCol}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Calibri'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D3E50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    // ──────────────────────────────────────────────────────────────
    // Auto-size Columns
    // ──────────────────────────────────────────────────────────────

    protected function autoSizeColumns($sheet, int $colCount): void
    {
        for ($i = 1; $i <= $colCount; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Legacy helper — kept for backwards-compat
    // ──────────────────────────────────────────────────────────────
    protected function styleHeader($sheet, int $row, int $colCount): void
    {
        $this->writeHeaderRow($sheet, $row, array_fill(0, $colCount, '')); // no-op cells, just style
    }

    // ──────────────────────────────────────────────────────────────
    // Save / Download
    // ──────────────────────────────────────────────────────────────

    protected function saveFile(Spreadsheet $spreadsheet, string $filename): void
    {
        $writer   = new Xlsx($spreadsheet);
        $filename = $filename . '.xlsx';
        $tempPath = storage_path('app/exports/' . $filename);

        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $writer->save($tempPath);

        response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->send();
    }

    // ──────────────────────────────────────────────────────────────
    // Utilities
    // ──────────────────────────────────────────────────────────────

    private function colLetter(int $colIndex): string
    {
        // 1-based: 1=A, 2=B, ..., 26=Z, 27=AA, ...
        $letter = '';
        while ($colIndex > 0) {
            $colIndex--;
            $letter    = chr(65 + ($colIndex % 26)) . $letter;
            $colIndex  = (int) ($colIndex / 26);
        }
        return $letter;
    }
}
