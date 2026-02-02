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

class TrialBalanceExport implements FromArray, WithHeadings, WithColumnFormatting, WithStyles, WithEvents, WithTitle
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
        $exportData = [];
        $totalDebitAwal = 0;
        $totalCreditAwal = 0;
        $totalDebitMutasi = 0;
        $totalCreditMutasi = 0;
        $totalDebitAkhir = 0;
        $totalCreditAkhir = 0;

        $digit  = get_arrangement('coa_digit');
        $digitStart = pow(10, $digit - 1);

        foreach ($this->data as $key=>$group) {
            // Add a row for the group level
            $exportData[] = [
                'Group Akun'=> str_pad($key+1, $digit, '0', STR_PAD_RIGHT),
                'Head Akun'=> null,
                'Sub Akun'=> null,
                'Akun' => null,
                null,
                'Name' => $group['name_group'],
                null,
                null,
                null,
                'Debit (Awal)' => null,
                'Credit (Awal)' => null,
                'Debit (Mutasi)' => null,
                'Credit (Mutasi)' => null,
                'Debit (Akhir)' => null,
                'Credit (Akhir)' => null,
            ];

            foreach ($group['coa_head'] as $head) {
                // Add a row for the head level
                $exportData[] = [
                    'Group Akun'=> null,
                    'Head Akun'=> $head['coa_head'],
                    'Sub Akun'=> null,
                    'Akun' => null,
                    null,
                    'Name' => $head['name_head'],
                    null,
                    null,
                    null,
                    'Debit (Awal)' => null,
                    'Credit (Awal)' => null,
                    'Debit (Mutasi)' => null,
                    'Credit (Mutasi)' => null,
                    'Debit (Akhir)' => null,
                    'Credit (Akhir)' => null,
                ];

                foreach ($head['coa_body'] as $body) {
                    // Add a row for the body level
                    $exportData[] = [
                        'Group Akun'=> null,
                        'Head Akun'=> null,
                        'Sub Akun'=> $body['coa_body'],
                        'Akun' => null,
                        null,
                        null,
                        'Name' => $body['name_body'],
                        null,
                        null,
                        'Debit (Awal)' => $body['debit_awal'],
                        'Credit (Awal)' => $body['credit_awal'],
                        'Debit (Mutasi)' => $body['debit_mutasi'],
                        'Credit (Mutasi)' => $body['credit_mutasi'],
                        'Debit (Akhir)' => $body['debit_akhir'],
                        'Credit (Akhir)' => $body['credit_akhir'],
                    ];

                    foreach ($body['coa'] as $coa) {
                        // Add a row for each individual coa
                        $exportData[] = [
                            'Group Akun'=> null,
                            'Head Akun'=> null,
                            'Sub Akun'=> null,
                            'Akun' => $coa['coa'],
                            null,
                            null,
                            null,
                            'Name' => $coa['name'],
                            null,
                            'Debit (Awal)' => $coa['debit_awal'],
                            'Credit (Awal)' => $coa['credit_awal'],
                            'Debit (Mutasi)' => $coa['debit_mutasi'],
                            'Credit (Mutasi)' => $coa['credit_mutasi'],
                            'Debit (Akhir)' => $coa['debit_akhir'],
                            'Credit (Akhir)' => $coa['credit_akhir'],
                        ];
                    }

                    //empty row
                    $exportData[] = [
                        'Group Akun'=> null,
                        'Head Akun'=> null,
                        'Sub Akun'=> null,
                        'Akun' => null,
                        null,
                        null,
                        'Name' => null,
                        null,
                        null,
                        'Debit (Awal)' => null,
                        'Credit (Awal)' => null,
                        'Debit (Mutasi)' => null,
                        'Credit (Mutasi)' => null,
                        'Debit (Akhir)' => null,
                        'Credit (Akhir)' => null,
                    ];

                    $totalDebitAwal += $body['debit_awal'];
                    $totalCreditAwal += $body['credit_awal'];
                    $totalDebitMutasi += $body['debit_mutasi'];
                    $totalCreditMutasi += $body['credit_mutasi'];
                    $totalDebitAkhir += $body['debit_akhir'];
                    $totalCreditAkhir += $body['credit_akhir'];
                }

                //total
                $exportData[] = [
                    'Group Akun'=> null,
                    'Head Akun'=> null,
                    'Sub Akun'=> null,
                    'Akun' => null,
                    null,
                    'Name' => null,
                    null,
                    null,
                    null,
                    'Debit (Awal)' => null,
                    'Credit (Awal)' => null,
                    'Debit (Mutasi)' => null,
                    'Credit (Mutasi)' => null,
                    'Debit (Akhir)' => null,
                    'Credit (Akhir)' => null,
                ];
            }
        }

        $exportData[] = [
            'Group Akun'=> null,
            'Head Akun'=> null,
            'Sub Akun'=> null,
            'Akun' => null,
            null,
            'Name' => 'T O T A L',
            null,
            null,
            null,
            'Debit (Awal)' => $totalDebitAwal,
            'Credit (Awal)' => $totalCreditAwal,
            'Debit (Mutasi)' => $totalDebitMutasi,
            'Credit (Mutasi)' => $totalCreditMutasi,
            'Debit (Akhir)' => $totalDebitAkhir,
            'Credit (Akhir)' => $totalCreditAkhir,
        ];
        return $exportData;
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [$this->title, '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [$this->periode, '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [],
            [],
            ['Group','Head','Body','Akun','','Nama Akun','','','','Saldo Awal','','Mutasi','','Saldo Akhir',''],
            ['','','','','','','','','','Debet','Kredit','Debet','Kredit','Debet','Kredit']
        ];
    }

    public function columnFormats(): array
    {
        return [
            'J' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'K' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'L' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'M' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'N' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'O' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
        ];
    }

    public function styles(Worksheet $sheet)
    {

        $data = $this->array();
        $boldRows = [];
        foreach ($data as $index => $row) {
            if ($row['Group Akun'] != null || $row['Head Akun'] != null || $row['Sub Akun'] != null || $row['Name'] == "T O T A L") {
                $boldRows[] = $index + 8;
            }
        }

        $sheet->mergeCells('A1:O1');
        $sheet->mergeCells('A2:O2');
        $sheet->mergeCells('A3:O3');

        $sheet->mergeCells('A6:A7');
        $sheet->mergeCells('B6:B7');
        $sheet->mergeCells('C6:C7');
        $sheet->mergeCells('D6:D7');
        $sheet->mergeCells('F6:H7');
        $sheet->mergeCells('J6:K6');
        $sheet->mergeCells('L6:M6');
        $sheet->mergeCells('N6:O6');

        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(6)->setRowHeight(20);
        $sheet->getRowDimension(7)->setRowHeight(20);

        $sheet->getColumnDimension('E')->setWidth(2);
        $sheet->getColumnDimension('F')->setWidth(2);
        $sheet->getColumnDimension('G')->setWidth(2);
        $sheet->getColumnDimension('H')->setWidth(44);
        $sheet->getColumnDimension('I')->setWidth(2);
        $sheet->getColumnDimension('J')->setWidth(16);
        $sheet->getColumnDimension('K')->setWidth(16);
        $sheet->getColumnDimension('L')->setWidth(16);
        $sheet->getColumnDimension('M')->setWidth(16);
        $sheet->getColumnDimension('N')->setWidth(16);
        $sheet->getColumnDimension('O')->setWidth(16);

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 22], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],

            'A6:O7' => ['font' => ['bold' => true, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
        ];

        foreach ($boldRows as $key=>$rowNumber) {
            $styles[$rowNumber] = ['font' => ['bold' => true]];
            if($key==count($boldRows)-1){
                $styles[$rowNumber] = ['font' => ['bold' => true, 'size' => 12,'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]];
                $sheet->getRowDimension($rowNumber)->setRowHeight(25);
            }
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
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A5:M5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A5:M5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(40);
                // $event->sheet->getDelegate()->getRowDimension('5')->setRowHeight(35);
                // $event->sheet->getDelegate()->getColumnDimension('H')->setWidth(50);
            },
        ];
    }
}
