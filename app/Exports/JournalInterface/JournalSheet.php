<?php

namespace App\Exports\JournalInterface;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;


class JournalSheet implements FromArray, WithTitle, WithHeadings, WithColumnFormatting, WithStyles
{
    protected $journal;
    protected $periode;

    public function __construct($journal, $periode)
    {
        $this->journal = $journal;
        $this->periode = $periode;
    }

    public function array(): array
    {
        $export_data = [];

        foreach ($this->journal['transactions'] as $transaction) {
            $export_data[] = [
                'date' => $transaction['date'],
                'transaction_number' => $transaction['transaction_number'],
                'invoice_number' => $transaction['invoice_number'],
                'efaktur_number' => $transaction['efaktur_number'],
                'from_or_to' => $transaction['from_or_to'],
                'description' => $transaction['description'],
                'coa' => null,
                'name' => null,
                'debit' => null,
                'credit' => null,
            ];
            foreach ($transaction['journals'] as $journalDetail) {
                $export_data[] = [
                    'date' => null,
                    'transaction_number' => null,
                    'invoice_number' => null,
                    'efaktur_number' => null,
                    'from_or_to' => null,
                    'description' => null,
                    'coa' => $journalDetail['coa'],
                    'name' => $journalDetail['name'],
                    'debit' => $journalDetail['debit'],
                    'credit' => $journalDetail['credit'],
                ];
            }
            // Tambahkan baris kosong sebagai pemisah biar rapi
            $export_data[] = [
                'date' => null,
                'transaction_number' => null,
                'invoice_number' => null,
                'efaktur_number' => null,
                'from_or_to' => null,
                'description' => null,
                'coa' => null,
                'name' => null,
                'debit' => null,
                'credit' => null,
            ];
        }

        return $export_data;
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['JOURNAL KHUSUS', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [$this->periode, '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [],
            [],
            [$this->journal['journal_name'], '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [],
            ['Date', 'Transaction Number', 'No Invoice', 'eFaktur',	'Payer/Payee', 'Description', 'COA',	'Name',	'Debit', 'Kredit'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'J' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
        ];
    }

    public function styles(Worksheet $sheet)
    {

        $startRow = 9;
        $data = $this->array();
        $isStriped = true; // Untuk memastikan selang-seling dimulai dari true (warna pertama)

        $endRow = $startRow + count($this->array()) - 1;

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(6)->setRowHeight(20);
        $sheet->getRowDimension($startRow-1)->setRowHeight(22);

        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(19);
        $sheet->getColumnDimension('C')->setWidth(19);
        $sheet->getColumnDimension('D')->setWidth(19);
        $sheet->getColumnDimension('E')->setWidth(19);

        $sheet->getColumnDimension('F')->setWidth(40);
        $sheet->getColumnDimension('G')->setWidth(9);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 24], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true, 'size' => 14, 'italic' => true ], 'alignment' => ['horizontal' => 'center']],
            'A6' => ['font' => ['bold' => true, 'size' => 16]],

            'A8:J8' => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
            "A{$startRow}:E{$endRow}" => ['alignment' => ['horizontal' => 'center']],
            "G{$startRow}:G{$endRow}" => ['alignment' => ['horizontal' => 'center']],
        ];
        $styles["A".$endRow] = ['font' => ['bold' => false]];

        foreach ($this->journal['transactions'] as $transaction) {
            $transactionRowCount = count($transaction['journals']) + 1; // 1 for the transaction row
            $sheet->mergeCells("F$startRow:F" . ($startRow + $transactionRowCount - 1)); // Merge Description column
            $sheet->getStyle("F$startRow:F" . ($startRow + $transactionRowCount - 1))
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setWrapText(true);

            // if ($isStriped) {
            //     $sheet->getStyle("A$startRow:J" . ($startRow + $transactionRowCount - 1))
            //         ->getFill()
            //         ->setFillType(Fill::FILL_SOLID)
            //         ->getStartColor()->setRGB('F8F8F8');
            //     $sheet->getStyle("A$startRow:J" . ($startRow + $transactionRowCount - 1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            // }

            $startRow += $transactionRowCount + 1; //karena ada baris baru untuk data kosong
        }

        return $styles;
    }


    public function title(): string
    {
        return $this->journal['journal_name'] ?? 'Unknown Journal';
    }

    public static function afterExport(AfterExport $event)
    {
        $spreadsheet = $event->getWriter()->getSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set kursor sel ke A1
        $sheet->setActiveCell('A1');
    }
}
