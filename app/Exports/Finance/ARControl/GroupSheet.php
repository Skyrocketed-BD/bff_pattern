<?php

namespace App\Exports\Finance\ARControl;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// class GroupSheet implements FromArray, WithTitle, WithHeadings, WithColumnFormatting, WithStyles, ShouldAutoSize
class GroupSheet implements FromArray, WithTitle, WithHeadings, WithColumnFormatting, WithStyles, WithColumnWidths
{
    protected $data;
    protected $title;

    public function __construct($data, $title)
    {
        $this->data     = $data;
        $this->title    = $title;
    }

    public function arrayOrig(): array
    {
        $export_data = [];

        foreach ($this->data['transactions'] as $transaction) {
            $payments = $transaction['payments'];
            $maxRows = max(1, count($payments)); // Tentukan jumlah baris yang dibutuhkan

            for ($i = 0; $i < $maxRows + 1; $i++) {
                $payment = $payments[$i] ?? null; // Ambil data payment jika ada

                if ($i === 0) {
                    // Baris pertama: tampilkan data transaksi dan data pembayaran pertama
                    $export_data[] = [
                        'date' => $transaction['date'],
                        'transaction_number' => $transaction['transaction_number'],
                        'reference_number' => $transaction['reference_number'],
                        'total_invoice' => $transaction['total_invoice'],
                        'total_paid' => $transaction['total_paid'],
                        'remaining' => $transaction['remaining'],
                        'payment_date' => $payment['date'] ?? null,
                        'payment_transaction_number' => $payment['transaction_number'] ?? null,
                        'payment_total' => $payment['total'] ?? null,
                    ];
                } else {
                    // Baris berikutnya: hanya tampilkan data pembayaran
                    $export_data[] = [
                        'date' => null,
                        'transaction_number' => null,
                        'reference_number' => null,
                        'total_invoice' => null,
                        'total_paid' => null,
                        'remaining' => null,
                        'payment_date' => $payment['date'] ?? null,
                        'payment_transaction_number' => $payment['transaction_number'] ?? null,
                        'payment_total' => $payment['total'] ?? null,
                    ];
                }
            }

            // Tambahkan baris kosong sebagai pemisah biar rapi
            $export_data[] = [
                'date' => null,
                'transaction_number' => null,
                'reference_number' => null,
                'total_invoice' => null,
                'total_paid' => null,
                'remaining' => null,
                'date' => null,
                'transaction_number' => null,
                'total' => null,
            ];
        }

        return $export_data;
    }

    public function array(): array
    {
        $export_data = [];

        foreach ($this->data['transactions'] as $transaction) {
            // Baris pertama: tampilkan data transaksi dan data pembayaran pertama
            $export_data[] = [
                'date'                      => $transaction['date'],
                'transaction_number'        => $transaction['transaction_number'],
                'reference_number'          => $transaction['reference_number'],
                'description'               => $transaction['description'],
                'total_invoice'             => $transaction['total_invoice'],
                'total_paid'                => $transaction['total_paid'],
                'remaining'                 => $transaction['remaining'],
                'payment_date'              => null,
                'payment_transaction_number' => null,
                'payment_description'       => null,
                'payment_total'             => null,
            ];

            foreach ($transaction['payments'] as $payment) {
                $export_data[] = [
                    'date'               => null,
                    'transaction_number' => null,
                    'reference_number'   => null,
                    'description'        => null,
                    'total_invoice'      => null,
                    'total_paid'         => null,
                    'remaining'          => null,
                    'payment_date'       => $payment['date'] ?? null,
                    'payment_transaction_number' => $payment['transaction_number'] ?? null,
                    'payment_description'       => $payment['description'] ?? null,
                    'payment_total'             => $payment['total'] ?? null,
                ];
            }

            // Tambahkan baris kosong sebagai pemisah biar rapi
            $export_data[] = [
                'date'               => null,
                'transaction_number' => null,
                'reference_number'   => null,
                'total_invoice'      => null,
                'total_paid'         => null,
                'remaining'          => null,
                'date'               => null,
                'transaction_number' => null,
                'total'              => null,
            ];
        }

        return $export_data;
    }

    /**
     * Menentukan judul untuk setiap sheet.
     */
    public function title(): string
    {
        // Mengambil nama kontak sebagai judul sheet, maksimal 31 karakter
        return substr($this->data['kontak_name'] ?? 'Unknown', 0, 31);
    }

    /**
     * Menentukan header file Excel.
     */
    public function headings(): array
    {
        return [
            [get_arrangement('company_name') ?? 'NAMA PERUSAHAAN'],
            [$this->title],
            [$this->data['kontak_name'] ?? ''],
            [],
            [],
            [
                'Date',
                'Invoice Number',
                'Reference Number',
                'Description',
                'Total Invoice',
                'Total Paid',
                'Remaining',
                'Payments',
                '',
                '',
                ''
            ],
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Date',
                'Transaction Number',
                'Description',
                'Total'
            ]
        ];
    }

    /**
     * Format kolom mata uang.
     */
    public function columnFormats(): array
    {
        $currencyFormat = '_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)';
        return [
            'E' => $currencyFormat,
            'F' => $currencyFormat,
            'G' => $currencyFormat,
            'K' => $currencyFormat,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 11, //date
            'B' => 27, //transaction number
            'C' => 20, //ref number
            'D' => 45, //description
            'E' => 18, //total invoice
            'F' => 18, //total paid
            'G' => 18, //remaining
            'H' => 11, //payment date
            'I' => 27, //payment number
            'J' => 45, //description
            'K' => 18, //total
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $data = $this->array();
        $startRow = 9;
        $endRow = $startRow + count($data) - 1;

        // Merge cells untuk judul
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        // Style untuk header utama
        $sheet->getRowDimension(2)->setRowHeight(25);
        $sheet->getRowDimension(6)->setRowHeight(23);
        $sheet->getRowDimension(7)->setRowHeight(23);
        $sheet->getRowDimension($startRow - 1)->setRowHeight(22);

        //merge cell untuk header table
        $sheet->mergeCells("A6:A7");
        $sheet->mergeCells("B6:B7");
        $sheet->mergeCells("C6:C7");
        $sheet->mergeCells("D6:D7");
        $sheet->mergeCells("E6:E7");
        $sheet->mergeCells("F6:F7");
        $sheet->mergeCells("G6:G7");
        $sheet->mergeCells("H6:K6");

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 24], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true, 'size' => 14, 'italic' => true], 'alignment' => ['horizontal' => 'center']],
            'A6' => ['font' => ['bold' => true, 'size' => 16]],

            "A6:G7" => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
            "H6:K7" => ['font' => ['bold' => true, 'italic' => true, 'size' => 12, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center'], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]],
            // "A{$startRow}:I{$endRow}" => ['alignment' => ['horizontal' => 'center']],
            // "G{$startRow}:G{$endRow}" => ['alignment' => ['horizontal' => 'center']],
        ];
        $styles["A" . $endRow] = ['font' => ['bold' => false]];

        foreach ($this->data['transactions'] as $transaction) {
            $transactionRowCount = count($transaction['payments']) + 1; // 1 for the transaction row

            $sheet->mergeCells("A$startRow:A" . ($startRow + $transactionRowCount - 1));
            $sheet->mergeCells("B$startRow:B" . ($startRow + $transactionRowCount - 1));
            $sheet->mergeCells("C$startRow:C" . ($startRow + $transactionRowCount - 1));
            $sheet->mergeCells("D$startRow:D" . ($startRow + $transactionRowCount - 1));
            $sheet->mergeCells("E$startRow:E" . ($startRow + $transactionRowCount - 1));
            $sheet->mergeCells("F$startRow:F" . ($startRow + $transactionRowCount - 1));
            $sheet->mergeCells("G$startRow:G" . ($startRow + $transactionRowCount - 1));

            $sheet->getStyle("D$startRow:D" . ($startRow + $transactionRowCount - 1))
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                // ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                // ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                ->setWrapText(true);

            $sheet->getStyle("A$startRow:C" . ($startRow + $transactionRowCount - 1))
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_TOP);

            $sheet->getStyle("D$startRow:D" . ($startRow + $transactionRowCount - 1))
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);

            $sheet->getStyle("H$startRow:I" . ($startRow + $transactionRowCount - 1))
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_TOP);

            $sheet->getStyle("J$startRow:J" . ($startRow + $transactionRowCount - 1))
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle("K$startRow:K" . ($startRow + $transactionRowCount - 1))
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP);

            // if ($isStriped) {
            //     $sheet->getStyle("A$startRow:J" . ($startRow + $transactionRowCount - 1))
            //         ->getFill()
            //         ->setFillType(Fill::FILL_SOLID)
            //         ->getStartColor()->setRGB('F8F8F8');
            //     $sheet->getStyle("A$startRow:J" . ($startRow + $transactionRowCount - 1))->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
            // }

            $startRow += $transactionRowCount + 1; //karena ada baris baru untuk data kosong

            // return $styles;
        }

        return $styles;
    }
}
