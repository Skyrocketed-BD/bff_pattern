<?php

namespace App\Exports;

use App\Http\Controllers\finance\ReportController;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

use App\Models\finance\AssetCoa;
use App\Models\finance\ClosingDepreciation;
use App\Models\finance\ClosingEntry;
use App\Models\finance\CoaGroup;
use App\Models\finance\GeneralLedger;
use App\Models\finance\ReportMenu;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use SebastianBergmann\CodeCoverage\Report\Xml\Report;

class JournalEntryExport implements FromArray, WithHeadings, WithColumnFormatting, WithStyles, WithEvents, WithTitle
{

    protected $data;
    public $periode;
    public $title;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function setPeriode($periode)
	{
		$this->periode = $periode;
		return $this;
	}

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        $export_data = [];

        foreach ($this->data as $ledger) {
            $export_data[] = [
                'date' => $ledger['date'],
                'transaction_number' => $ledger['transaction_number'],
                'description' => $ledger['description'],
                'total_debit' => $ledger['total_debit'],
                'total_kredit' => $ledger['total_kredit'],
                'coa' => null,
                'name' => null,
                'type' => null,
                'amount' => null,
            ];

            foreach ($ledger['journals'] as $journal) {
                $export_data[] = [
                    'date' => null, // Kosong untuk baris jurnal
                    'transaction_number' => null, // Kosong untuk baris jurnal
                    'description' => null, // Kosong untuk baris jurnal
                    'total_debit' => null, // Kosong untuk baris jurnal
                    'total_kredit' => null, // Kosong untuk baris jurnal
                    'coa' => $journal['coa'],
                    'name' => $journal['name'],
                    'type' => $journal['type'],
                    'amount' => $journal['amount'],
                ];
            }

            // Tambahkan baris kosong sebagai pemisah
            $export_data[] = [
                'date' => null,
                'transaction_number' => null,
                'description' => null,
                'total_debit' => null,
                'total_kredit' => null,
                'coa' => null,
                'name' => null,
                'type' => null,
                'amount' => null,
            ];
        }

        return $export_data;
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['JOURNAL ENTRY', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [$this->periode, '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [],
            [],
            ['Date','Transaction Number','Description','Total Debit','Total Kredit','COA','Name','Type','Amount'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'E' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'I' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
        ];
    }

    public function styles(Worksheet $sheet)
    {

        $data = $this->array();
        $boldRows = [];
        // foreach ($data as $index => $row) {
        //     if ($row['date'] != null ) {
        //         $boldRows[] = $index + 7;
        //     }
        // }

        $startRow = 7;
        $endRow = $startRow + count($this->array()) - 1;

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(6)->setRowHeight(20);
        $sheet->getRowDimension(7)->setRowHeight(20);

        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(17);
        $sheet->getColumnDimension('E')->setWidth(17);
        $sheet->getColumnDimension('F')->setWidth(9);
        $sheet->getColumnDimension('G')->setWidth(25);
        $sheet->getColumnDimension('H')->setWidth(7);
        $sheet->getColumnDimension('I')->setWidth(17);

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 22], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],

            'A6:I6' => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
            "A{$startRow}:A{$endRow}" => ['alignment' => ['horizontal' => 'center']],
            "B{$startRow}:B{$endRow}" => ['alignment' => ['horizontal' => 'center']],
            "F{$startRow}:F{$endRow}" => ['alignment' => ['horizontal' => 'center']],
            "H{$startRow}:H{$endRow}" => ['alignment' => ['horizontal' => 'center']],
        ];

        foreach ($boldRows as $key=>$rowNumber) {
            $styles[$rowNumber] = ['font' => ['bold' => true]];
            // if($key==count($boldRows)-1){
            //     $styles[$rowNumber] = ['font' => ['bold' => true, 'size' => 12,'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]];
            //     $sheet->getRowDimension($rowNumber)->setRowHeight(25);
            // }
        }

        return $styles;
    }

    // public function startCell(): string
    // {
    //     return 'A6';
    // }

    // public function columnWidths(): array
    // {
    //     return [
    //         'E' => 2,
    //         'F' => 2,
    //     ];
    // }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // $sheet = $event->sheet->getDelegate();

                // // Alignment horizontal dan vertical untuk header baris 5
                // $sheet->getStyle('A5:M5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                // $sheet->getStyle('A5:M5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // // Tentukan row start dari data Anda
                // $startRow = 8;
                // $endRow = $startRow + count($this->data) - 1; // Hitung panjang data

                // // Set alignment horizontal untuk kolom tertentu mulai dari baris ke-8
                // $sheet->getStyle("G{$startRow}:H{$endRow}")
                //     ->getAlignment()
                //     ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // // Debug untuk memastikan rentang yang diterapkan
                // echo "Range applied: G{$startRow}:H{$endRow}";

                // // Pastikan gaya diterapkan di area yang spesifik
                // $sheet->getStyle("G{$startRow}:H{$startRow}")
                //     ->getAlignment()
                //     ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // // Vertically center untuk memastikannya selaras
                // $sheet->getStyle("G{$startRow}:H{$endRow}")
                //     ->getAlignment()
                //     ->setVertical(Alignment::VERTICAL_CENTER);

                // $sheet->getStyle('F8')->applyFromArray([
                //     'font' => [
                //         'bold' => true,
                //         'color' => ['rgb' => 'FF0000'],
                //         'size' => 16,
                //     ],
                // ]);
            },
        ];
    }

}
