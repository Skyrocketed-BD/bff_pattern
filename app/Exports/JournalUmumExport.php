<?php

namespace App\Exports;

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


class JournalUmumExport implements FromArray, WithTitle, WithHeadings, WithColumnFormatting, WithStyles
{
    protected $datas;
    protected $periode;

    public function __construct(array $datas)
    {
        $this->datas = $datas;
    }

    public function setPeriode($periode)
	{
		$this->periode = $periode;
		return $this;
	}

    public function array(): array
    {
        $export_data = [];
        $datas = $this->datas;

        foreach ($datas as $transaction) {
            $export_data[] = [
                'date'              => $transaction['date'],
                'transaction_number'=> $transaction['transaction_number'],
                'description'       => $transaction['description'],
                'amount'            => $transaction['amount'],

                'coa'               => null,
                'name'              => null,
                'debit'             => null,
                'credit'            => null,
            ];
            foreach ($transaction['journals'] as $journal) {
                $export_data[] = [
                    'date'              => null,
                    'transaction_number'=> null,
                    'description'       => null,
                    'amount'            => null,

                    'coa'               => $journal['coa'],
                    'name'              => $journal['name'],
                    'debit'             => $journal['debit'],
                    'credit'            => $journal['credit'],
                ];
            }
            // Tambahkan baris kosong sebagai pemisah biar rapi
            $export_data[] = [
                'date'              => null,
                'transaction_number'=> null,
                'description'       => null,
                'amount'            => null,
                'coa'               => null,
                'name'              => null,
                'debit'             => null,
                'credit'            => null,
            ];
        }

        return $export_data;
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['JOURNAL UMUM', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [$this->periode, '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [],
            [],
            [],
            ['Date', 'Transaction Number', 'Description', 'Amount', 'COA',	'Name',	'Debit', 'Kredit'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'G' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'H' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
        ];
    }

    public function styles(Worksheet $sheet)
    {

        $startRow = 7;
        $data = $this->array();
        $isStriped = true;

        $endRow = $startRow + count($this->array()) - 1;

        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension($startRow-1)->setRowHeight(22);

        $sheet->getColumnDimension('A')->setWidth(11);
        $sheet->getColumnDimension('B')->setWidth(19);
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(9);
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(14);

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 24], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true, 'size' => 14, 'italic' => true ], 'alignment' => ['horizontal' => 'center']],
            'A'.$startRow => ['font' => ['bold' => true, 'size' => 16]],
            "A{$startRow}:H{$startRow}" => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],

            "A{$startRow}:A{$endRow}" => ['alignment' => ['horizontal' => 'center']],
            "B{$startRow}:B{$endRow}" => ['alignment' => ['horizontal' => 'center']],
            "E{$startRow}:E{$endRow}" => ['alignment' => ['horizontal' => 'center']],
        ];

        foreach ($this->datas as $transaction) {
            $transactionRowCount = count($transaction['journals']) + 1;
            $sheet->mergeCells("C".($startRow+1).":C" . ($startRow + $transactionRowCount ));
            $sheet->getStyle("A$startRow:C" . ($startRow + $transactionRowCount - 1))
                ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

            $startRow += $transactionRowCount + 1; //baris baru untuk data kosong
        }

        $styles["A".$endRow] = ['font' => ['bold' => false]];


        return $styles;
    }


    public function title(): string
    {
        return 'JURNAL UMUM';
    }

    public static function afterExport(AfterExport $event)
    {
        $spreadsheet = $event->getWriter()->getSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set kursor sel ke A1
        $sheet->setActiveCell('A1');
    }
}
