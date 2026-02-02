<?php

namespace App\Exports\Operation;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OreShippingDetailSheet implements FromArray, WithTitle, WithHeadings, WithColumnFormatting, WithStyles
{
    protected $data;
    protected $periode;
    protected $title;

    public function __construct(array $data, $title, $periode)
    {
        $this->data = $data;
        $this->title = $title;
        $this->periode = $periode;
    }

    public function array(): array
    {
        $exportData = [];

        foreach ($this->data as $value) {
            // Add a row for the group level
            $exportData[] = [
                'Tanggal'           => $value['date'],
                'Kontraktor'        => $value['kontraktor'],
                'Buyer'             => $value['buyer'],
                'Sarana Angkut'     => $value['tug_boat'] . '/' . $value['barge'],
                'Penerima'          => $value['consignee'],
                'SI Number'         => $value['number_si'],
                'Invoice Final'     => $value['inv_final'],
                'Tonase Akhir'      => $value['tonage_final'],
                'Ni Provisional'    => $value['ni_provision'] / 100,
                'MC Provisional'    => $value['mc_provision'] / 100,
                'Harga Provisional' => $value['price_provision'],
                'Ni Final'          => $value['ni_final'] / 100,
                'MC Final'          => $value['mc_final'] / 100,
                'Harga Final'       => $value['price_final']
            ];
        }

        return $exportData;
    }

    public function title(): string
    {
        return 'Ore Shipping';
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [$this->title, '', '', '', '', '', '', '', '', '', '', ''],
            [$this->periode, '', '', '', '', '', '', '', '', '', '', ''],
            [],
            [],
            ['Tanggal', 'Nama Kontraktor', 'Buyer/Trader', 'Penerima', 'Sarana Angkut', 'SI Number', 'Invoice Final', 'Tonase Akhir', 'Nilai Kadar Provisional', '', 'Harga Jual Provisional', 'Nilai Kadar Final', '', 'Harga Jual Final'],
            ['', '', '', '', '', '', '', '', 'Ni', 'MC', '', 'Ni', 'MC', ''],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_PERCENTAGE_00,
            'J' => NumberFormat::FORMAT_PERCENTAGE_00,
            'K' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
            'L' => NumberFormat::FORMAT_PERCENTAGE_00,
            'M' => NumberFormat::FORMAT_PERCENTAGE_00,
            'N' => '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)',
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

        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('A3:N3');

        $sheet->mergeCells('A6:A7');
        $sheet->mergeCells('B6:B7');
        $sheet->mergeCells('C6:C7');
        $sheet->mergeCells('D6:D7');
        $sheet->mergeCells('E6:E7');
        $sheet->mergeCells('F6:F7');
        $sheet->mergeCells('G6:G7');
        $sheet->mergeCells('H6:H7');
        $sheet->mergeCells('I6:J6');
        $sheet->mergeCells('K6:K7');
        $sheet->mergeCells('L6:M6');
        $sheet->mergeCells('N6:N7');

        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(6)->setRowHeight(20);
        $sheet->getRowDimension(7)->setRowHeight(20);

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(36);
        $sheet->getColumnDimension('D')->setWidth(46);
        $sheet->getColumnDimension('E')->setWidth(36);
        $sheet->getColumnDimension('F')->setWidth(26);
        $sheet->getColumnDimension('G')->setWidth(26);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(12);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(21);
        $sheet->getColumnDimension('L')->setWidth(12);
        $sheet->getColumnDimension('M')->setWidth(12);
        $sheet->getColumnDimension('N')->setWidth(21);

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 22], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],

            'A6:O7' => ['font' => ['bold' => true, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
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
