<?php

namespace App\Exports\Operation;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractorExport extends DefaultValueBinder implements FromArray, WithTitle, WithHeadings, WithStyles, WithCustomValueBinder, WithCustomStartCell
{
    protected $data;
    protected $title;

    public function __construct(array $data, $title)
    {
        $this->data = $data;
        $this->title = $title;
    }

    public function array(): array
    {
        $exportData = [];

        $exportData = array_map(function ($value, $key) {
            return [
                'no'          => $key + 1,
                'company'     => $value['company'],
                'leader'      => $value['leader'],
                'npwp'        => $value['npwp'],
                'telepon'     => $value['telepon'],
                'email'       => $value['email'],
                'address'     => $value['address'],
                'postal_code' => $value['postal_code'],
                'website'     => $value['website'],
                'initial'     => $value['initial'],
                'capital'     => $value['capital'],
            ];
        }, $this->data, array_keys($this->data));

        return $exportData;
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        // else return default behavior
        return parent::bindValue($cell, $value);
    }

    public function title(): string
    {
        return 'Contractor';
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', ''],
            [$this->title, '', '', '', '', '', '', '', '', '', ''],
            [],
            ['No.', 'Nama Kontraktor','Leader','NPWP','Telepon','Email','Alamat','Kode Pos','Website','Initial','Capital'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A2:K2');
        $sheet->mergeCells('A3:K3');

        $sheet->getRowDimension(3)->setRowHeight(25);
        $sheet->getRowDimension(5)->setRowHeight(25);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(36);
        $sheet->getColumnDimension('G')->setWidth(65);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(16);

        $styles = [
            2 => ['font' => ['bold' => true]],
            'A:K' => ['alignment' => ['vertical' => 'center']],
            'A' => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'left', 'vertical' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 22], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            // 'A3' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],

            'A5:K5' => ['font' => ['bold' => true, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],

            'G' => ['wrapText' => true, 'alignment' => ['wrapText' => true]],
            'H' => ['alignment' => ['horizontal' => 'center']],
            'J' => ['alignment' => ['horizontal' => 'center']],
            'K' => ['alignment' => ['horizontal' => 'center']],
        ];

        return $styles;
    }

}
