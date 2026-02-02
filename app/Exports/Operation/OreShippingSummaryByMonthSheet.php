<?php

namespace App\Exports\Operation;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class OreShippingSummaryByMonthSheet implements FromArray, WithTitle, WithHeadings, WithColumnFormatting, WithStyles
{
    protected $data;
    protected $periode;

    public function __construct(array $data, $periode)
    {
        $this->data = $data;
        $this->periode = $periode;
    }


    public function array(): array
    {
        $summary = [];
        $grouped = collect($this->data)->groupBy(function ($item) {
            return Carbon::parse($item['departure_date'])->format('m'); // Ambil bulan numerik
        });

        $months = [
            '01' => 'January',  '02' => 'February', '03' => 'Maret',
            '04' => 'April',    '05' => 'Mei',      '06' => 'Juni',
            '07' => 'Juli',     '08' => 'Agustus',  '09' => 'September',
            '10' => 'Oktober',  '11' => 'November', '12' => 'Desember',
        ];

        $totalTonase = 0;
        $totalInvoice = 0;
        $no = 1;

        foreach ($months as $num => $name) {
            $group = $grouped->get($num, collect());

            $tonase = $group->sum('tonage_final');
            $invoice = $group->sum('price_final');

            $summary[] = [
                $no++,
                $name,
                $tonase ?: 0,
                $invoice ?: 0,
            ];

            $totalTonase += $tonase;
            $totalInvoice += $invoice;
        }

        // Tambahkan total baris akhir
        $summary[] = [
            '', 'Total', $totalTonase, $totalInvoice,
        ];

        return $summary;
    }

    public function title(): string
    {
        return 'Monthly Summary';
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Monthly Summary', '', '', '', '', '', '', '', '', '', ''],
            [$this->periode, '', '', '', '', '', '', '', '', '', ''],
            [],
            [],
            ['No','Bulan','Total Tonase','Total Nilai Invoice'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'D' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
        ];
    }

    public function styles(Worksheet $sheet)
    {

        $data = $this->array();
        $boldRows = [];
        // foreach ($data as $index => $row) {
        //     if ($row['Group Akun'] != null || $row['Head Akun'] != null || $row['Sub Akun'] != null || $row['Name'] == "T O T A L") {
        //         $boldRows[] = $index + 8;
        //     }
        // }

        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('A2:D2');
        $sheet->mergeCells('A3:D3');


        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(6)->setRowHeight(20);
        $sheet->getRowDimension(7)->setRowHeight(20);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(26);

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 22], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],

            'A6:D6' => ['font' => ['bold' => true, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
        ];

        // foreach ($boldRows as $key=>$rowNumber) {
        //     $styles[$rowNumber] = ['font' => ['bold' => true]];
        //     if($key==count($boldRows)-1){
        //         $styles[$rowNumber] = ['font' => ['bold' => true, 'size' => 12,'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]];
        //         $sheet->getRowDimension($rowNumber)->setRowHeight(25);
        //     }
        // }

        return $styles;
    }
}
