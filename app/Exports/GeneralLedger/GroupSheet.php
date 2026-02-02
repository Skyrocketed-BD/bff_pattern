<?php

namespace App\Exports\GeneralLedger;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GroupSheet implements WithTitle, WithEvents
{
    protected $data;
    protected $periode;

    public function __construct($data, $periode)
    {
        $this->data = $data;
        $this->periode = $periode;
    }

    public function title(): string
    {
        return $this->data['group_name'] ?? 'Unknown Group';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $row = 1;
                $datas = $this->data;

                // Atur lebar kolom
                $sheet->getColumnDimension('A')->setWidth(11); // NO
                $sheet->getColumnDimension('B')->setWidth(14); // DATE
                $sheet->getColumnDimension('C')->setWidth(24); // TRANSACTION NUMBER
                $sheet->getColumnDimension('D')->setWidth(24); // CONTACT
                $sheet->getColumnDimension('E')->setWidth(50); // DESCRIPTION
                $sheet->getColumnDimension('F')->setWidth(24); // REF
                $sheet->getColumnDimension('G')->setWidth(20); // DEBIT
                $sheet->getColumnDimension('H')->setWidth(20); // CREDIT
                $sheet->getColumnDimension('I')->setWidth(20); // BALANCE

                foreach ($datas['groups'] as $groups) {
                    // Header COA Group
                    $sheet->setCellValue("A{$row}", "Coa Group");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->setCellValue("B{$row}", $datas['coa_group']);
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                    $sheet->mergeCells("C{$row}:I{$row}");
                    $sheet->setCellValue("C{$row}", get_arrangement('company_name'));
                    $sheet->getStyle("C{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 14],
                        'alignment' => ['horizontal' => 'center']
                    ]);
                    $row++;

                    // Header COA Head
                    $sheet->setCellValue("A{$row}", "Coa Head");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->setCellValue("B{$row}", $groups['coa_head']);
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                    $sheet->mergeCells("C{$row}:I{$row}");
                    $sheet->setCellValue("C{$row}", $groups['coa_name']);
                    $sheet->getStyle("C{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '0070C0']],
                        'alignment' => ['horizontal' => 'center']
                    ]);
                    $row++;

                    // Header COA Body
                    $sheet->setCellValue("A{$row}", "Coa Body");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->setCellValue("B{$row}", $groups['coa_body']);
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                    $sheet->mergeCells("C{$row}:I{$row}");
                    $sheet->setCellValue("C{$row}", "BUKU BESAR");
                    $sheet->getStyle("C{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 13],
                        'alignment' => ['horizontal' => 'center']
                    ]);
                    $row++;

                    // Header COA
                    $sheet->setCellValue("A{$row}", "Coa");
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->setCellValue("B{$row}", $groups['coa']);
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                    $sheet->mergeCells("C{$row}:I{$row}");
                    $sheet->setCellValue("C{$row}", $this->periode);
                    $sheet->getStyle("C{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12, 'italic' => true],
                        'alignment' => ['horizontal' => 'center']
                    ]);
                    $row += 2;

                    // Header Tabel
                    $sheet->setCellValue("A{$row}", 'NO');
                    $sheet->setCellValue("B{$row}", 'DATE');
                    $sheet->setCellValue("C{$row}", 'TRANSACTION NUMBER');
                    $sheet->setCellValue("D{$row}", 'CONTACT');
                    $sheet->setCellValue("E{$row}", 'DESCRIPTION');
                    $sheet->setCellValue("F{$row}", 'REF');
                    $sheet->setCellValue("G{$row}", 'DEBIT');
                    $sheet->setCellValue("H{$row}", 'CREDIT');
                    $sheet->setCellValue("I{$row}", 'BALANCE');
                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '0070C0']],
                        'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(30);
                    $row++;

                    // Data Transaksi
                    $no = 1;
                    foreach ($groups['transactions'] as $transaction) {
                        $sheet->setCellValue("A{$row}", $no++);
                        $sheet->setCellValue("B{$row}", $transaction['date']);
                        $sheet->setCellValue("C{$row}", $transaction['transaction_number']);
                        $sheet->setCellValue("D{$row}", $transaction['contact'] ?? '');
                        $sheet->setCellValue("E{$row}", $transaction['description']);
                        $sheet->setCellValue("F{$row}", $transaction['ref_number']);
                        $sheet->setCellValue("G{$row}", $transaction['debit']);
                        $sheet->setCellValue("H{$row}", $transaction['credit']);
                        $sheet->setCellValue("I{$row}", $transaction['balance']);

                        // Center alignment kolom tertentu
                        foreach (['A', 'B', 'C', 'F'] as $col) {
                            $sheet->getStyle("{$col}{$row}")
                                ->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }

                        // Format angka Rp di G,H,I
                        foreach (['G', 'H', 'I'] as $col) {
                            $sheet->getStyle("{$col}{$row}")
                                ->getNumberFormat()
                                ->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                        }

                        // Wrap text di kolom E (DESCRIPTION)
                        $sheet->getStyle("E{$row}")
                            ->getAlignment()
                            ->setWrapText(true);

                        // Vertical top untuk semua kolom
                        foreach (range('A', 'I') as $col) {
                            $sheet->getStyle("{$col}{$row}")
                                ->getAlignment()
                                ->setVertical(Alignment::VERTICAL_TOP);
                        }

                        $row++;
                    }

                    // Total
                    $sheet->mergeCells("A{$row}:F{$row}");
                    $sheet->setCellValue("A{$row}", 'JUMLAH');
                    $sheet->setCellValue("G{$row}", '=SUM(G' . ($row - count($groups['transactions'])) . ":G" . ($row - 1) . ')');
                    $sheet->setCellValue("H{$row}", '=SUM(H' . ($row - count($groups['transactions'])) . ":H" . ($row - 1) . ')');
                    $sheet->setCellValue("I{$row}", '=I' . ($row - 1));
                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '0070C0']],
                        'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(18);
                    foreach (['G', 'H', 'I'] as $col) {
                        $sheet->getStyle("{$col}{$row}")
                            ->getNumberFormat()
                            ->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                    }

                    $row += 3;
                }
            },
        ];
    }
}
