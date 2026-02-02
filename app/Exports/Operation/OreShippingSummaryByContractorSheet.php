<?php

namespace App\Exports\Operation;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;

use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OreShippingSummaryByContractorSheet implements FromArray, WithTitle, WithHeadings, WithColumnFormatting, WithStyles
{
    protected $data;
    protected $periode;

    public function __construct(array $data, $periode)
    {
        $this->data = $data;
        $this->periode = $periode;
    }

    // public function headings(): array
    // {
    //     return [
    //         'No',
    //         'Kontraktor',
    //         'Jumlah Pengapalan (BG)',
    //         'Nilai Invoice',
    //         'Ni',
    //         'Fe',
    //         'MC',
    //         'SiMg',
    //         'Total Tonase Terjual',
    //     ];
    // }

    public function array(): array
    {
        $grouped = collect($this->data)->groupBy('kontraktor');

        $summary = [];
        $no = 1;
        $grandTotalShipment = 0;
        $grandTotalTonase = 0;
        $grandTotalInvoice = 0;

        foreach ($grouped as $contractor => $records) {
            $totalTonase = $records->sum('tonage_final');
            $totalInvoice = $records->sum('price_final');
            $shipmentCount = $records->count();

            // Weighted average (sum product / sum tonase)
            $ni = $this->weightedAverage($records, 'ni_final', $totalTonase)/100;
            // $fe = $this->weightedAverage($records, 'fe_content', $totalTonase);
            $mc = $this->weightedAverage($records, 'mc_final', $totalTonase)/100;
            // $simg = $this->weightedAverage($records, 'simg_content', $totalTonase);

            $summary[] = [
                $no++,
                $contractor,
                $shipmentCount,
                $totalInvoice ?: '',
                $ni,
                $mc,
                // $mc,
                // $simg,
                $totalTonase ?: '',
            ];

            $grandTotalShipment += $shipmentCount;
            $grandTotalTonase += $totalTonase;
            $grandTotalInvoice += $totalInvoice;
        }

        // Tambahkan baris total
        $summary[] = [
            '',
            'Total',
            $grandTotalShipment,
            $grandTotalInvoice,
            '', '',
            $grandTotalTonase,
        ];

        return $summary;
    }

    private function weightedAverage($records, $field, $totalTonase)
    {
        if ($totalTonase <= 0) {
            return '';
        }

        $sumProduct = $records->sum(function ($item) use ($field) {
            return ($item[$field] ?? 0) * ($item['tonage_final'] ?? 0);
        });

        return round($sumProduct / $totalTonase, 4);
    }

    public function title(): string
    {
        return 'Summary by Contractor';
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Summary by Contractor', '', '', '', '', '', '', '', '', '', ''],
            [$this->periode, '', '', '', '', '', '', '', '', '', ''],
            [],
            [],
            ['No','Nama Kontraktor','Jumlah Pengapalan','Nilai Invoice','Ni','MC','Tonase Terjual'],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'E' => NumberFormat::FORMAT_PERCENTAGE_00,
            'F' => NumberFormat::FORMAT_PERCENTAGE_00,
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

        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');

        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(6)->setRowHeight(25);
        $sheet->getRowDimension(7)->setRowHeight(20);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(26);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(18);

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 22], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],

            'A6:G6' => ['font' => ['bold' => true, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
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
