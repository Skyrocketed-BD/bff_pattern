<?php

namespace App\Exports;

use App\Http\Controllers\finance\ReportController;
use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetDepreciationExport implements WithTitle, WithEvents, WithHeadings, WithStyles
{
    protected $data;
    protected $period;
    protected $setAllBorder;
    protected $setTitle;

    public function __construct(array $data, string $period)
    {
        $this->data = $data;
        $this->period = $period; // Periode dalam format YYYY-MM
    }

    public function title(): string
    {
        return 'Daftar Asset';
    }

    public function setAllBorder($setAllBorder)
    {
        $this->setAllBorder=$setAllBorder;
        return $this;
    }


    public function setTitle($setTitle)
    {
        $this->setTitle=$setTitle;
        return $this;
    }

    public static function afterExport(AfterExport $event)
    {
        $spreadsheet = $event->getWriter()->getSpreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set kursor sel ke A1
        $sheet->setActiveCell('A1');
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $initial_row = 5;
                $row = 5; // Mulai dari baris pertama
                $startColumnIndex = 9; // Kolom awal (I)
                $datas = $this->data['data'];
                $total_group_bulan = count($datas[0]['items'][0]['data_penyusutan']);

                $sum_nilai_perolehan = 0;

                $sum_penyusutan = [];
                $sum_akum_penyusutan = [];
                $sum_nilai_buku = [];


                // atur lebar kolom
                $sheet->getColumnDimension('A')->setWidth(4);
                $sheet->getColumnDimension('B')->setWidth(42);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(4); //qty
                $sheet->getColumnDimension('E')->setWidth(18); //harga_per_unit
                $sheet->getColumnDimension('F')->setWidth(18); //nilai_perolehan
                $sheet->getColumnDimension('G')->setWidth(15); //kelompok
                $sheet->getColumnDimension('H')->setWidth(6); //tarif

                // Buat Header Tabel
                // Bulan Dulu
                $sheet->setCellValue("A{$row}", '');
                $sheet->setCellValue("B{$row}", '');
                $sheet->setCellValue("C{$row}", '');
                $sheet->setCellValue("D{$row}", 'NILAI AKTIVA');
                $sheet->setCellValue("E{$row}", '');
                $sheet->setCellValue("F{$row}", '');
                $sheet->setCellValue("G{$row}", 'KELOMPOK');
                $sheet->setCellValue("H{$row}", '%');

                // merge cell
                $sheet->mergeCells("D{$row}:F{$row}");

                $columnsPerPeriod = $this->generatePeriodColumns();
                for ($key=0; $key < $total_group_bulan ; $key++) {
                    $colPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3));
                    $colAkumPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 1);
                    $colNilaiBuku = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 2);
                    $sheet->mergeCells("{$colPenyusutan}{$row}:{$colNilaiBuku}{$row}");
                    $sheet->setCellValue("{$colPenyusutan}{$row}", $columnsPerPeriod[$key]);
                }

                $sheet->getStyle("A{$row}:{$colNilaiBuku}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '0070C0']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                ]);
                $sheet->getRowDimension($row)->setRowHeight(30);

                // lalu header
                $row++;
                $sheet->mergeCells("A{$row}:A".$row-1);
                $sheet->mergeCells("B{$row}:B".$row-1);
                $sheet->mergeCells("C{$row}:C".$row-1);
                $sheet->mergeCells("G{$row}:G".$row-1);
                $sheet->mergeCells("H{$row}:H".$row-1);

                $sheet->setCellValue("A".$row-1, 'NO');
                $sheet->setCellValue("B".$row-1, 'JENIS AKTIVA');
                $sheet->setCellValue("C".$row-1, 'TGL PEROLEHAN');
                $sheet->setCellValue("D{$row}", 'QTY');
                $sheet->setCellValue("E{$row}", 'HARGA PER UNIT');
                $sheet->setCellValue("F{$row}", 'HARGA PEROLEHAN');

                $sheet->getStyle("C".$row-1)->getAlignment()->setWrapText(true);

                for ($key=0; $key < $total_group_bulan ; $key++) {
                    $colPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3));
                    $colAkumPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 1);
                    $colNilaiBuku = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 2);

                    $sheet->setCellValue("{$colPenyusutan}{$row}", 'Penyusutan');
                    $sheet->setCellValue("{$colAkumPenyusutan}{$row}",'Akum Penyusutan');
                    $sheet->setCellValue("{$colNilaiBuku}{$row}", 'Nilai Buku');
                }

                $sheet->getStyle("A{$row}:{$colNilaiBuku}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '0070C0']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                ]);
                $sheet->getRowDimension($row)->setRowHeight(30);

                $row++;
                $no = 1;
                foreach ($datas as $assets) {
                    $items = $assets['items'];

                    // Tambahkan Group Asset
                    $sheet->setCellValue("A{$row}", angka_romawi($no++));
                    $sheet->setCellValue("B{$row}", $assets['asset_coa']);
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true);

                    $row++;
                    // Tambahkan Asset Item
                    foreach ($assets['items'] as $item) {
                        $sheet->setCellValue("B{$row}", $item['name']);
                        $sheet->setCellValue("C{$row}", $item['date']);
                        $sheet->setCellValue("D{$row}", $item['qty']);
                        $sheet->setCellValue("E{$row}", $item['price']);
                        $sheet->setCellValue("F{$row}", $item['nilai_perolehan']);
                        $sheet->setCellValue("G{$row}", $item['group']);
                        $sheet->setCellValue("H{$row}", $item['rate']);

                        // untuk kebutuhan grand_total
                        $sum_nilai_perolehan += $item['nilai_perolehan'];

                        // Ambil data penyusutan tiap bulan
                        foreach ($item['data_penyusutan'] as $key => $penyusutan) {
                            $colPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3));
                            $colAkumPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 1);
                            $colNilaiBuku = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 2);

                            $sheet->setCellValue("{$colPenyusutan}{$row}", $penyusutan['penyusutan']);
                            $sheet->setCellValue("{$colAkumPenyusutan}{$row}", $penyusutan['akum_penyusutan']);
                            $sheet->setCellValue("{$colNilaiBuku}{$row}", $penyusutan['nilai_buku']);

                            $sheet->getStyle("{$colPenyusutan}{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                            $sheet->getStyle("{$colAkumPenyusutan}{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                            $sheet->getStyle("{$colNilaiBuku}{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');

                            $sheet->getColumnDimension("{$colPenyusutan}")->setWidth(17);
                            $sheet->getColumnDimension("{$colAkumPenyusutan}")->setWidth(17);
                            $sheet->getColumnDimension("{$colNilaiBuku}")->setWidth(17);

                            $sum_penyusutan["{$colPenyusutan}"][] = $penyusutan['penyusutan'];
                            $sum_akum_penyusutan["{$colAkumPenyusutan}"][] = $penyusutan['akum_penyusutan'];
                            $sum_nilai_buku["{$colNilaiBuku}"][]= $penyusutan['nilai_buku'];
                        }

                        // rata tengah (center alignment)
                        foreach (['A', 'C', 'D', 'H'] as $column) {
                            $sheet->getStyle("{$column}{$row}")
                                ->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }

                        // format angka dengan format Rp
                        foreach (['E','F'] as $column) {
                            $sheet->getStyle("{$column}{$row}")
                                ->getNumberFormat()
                                ->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                        }

                        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_0);

                        $row++;
                    }

                    // Tambahkan Baris Total
                    $sheet->mergeCells("A{$row}:B{$row}");
                    $sheet->setCellValue("A{$row}", "J U M L A H");
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['horizontal'=>'center','vertical' => 'center'],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                    ]);
                    $sheet->getStyle("C{$row}:H{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['vertical' => 'center'],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                    ]);

                    $sheet->setCellValue(
                        "F{$row}",
                        "=SUM(F" . ($row - count($items)) . ":F" . ($row - 1) . ")"
                    );
                    $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');

                    $colTerakhir = '';
                    foreach ($items[0]['data_penyusutan'] as $key => $penyusutan) {
                        $colPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3));
                        $colAkumPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 1);
                        $colNilaiBuku = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 2);

                        // Menggunakan formula SUM untuk total
                        $sheet->setCellValue(
                            "{$colPenyusutan}{$row}",
                            "=SUM({$colPenyusutan}" . ($row - count($items)) . ":{$colPenyusutan}" . ($row - 1) . ")"
                        );
                        $sheet->setCellValue(
                            "{$colAkumPenyusutan}{$row}",
                            "=SUM({$colAkumPenyusutan}" . ($row - count($items)) . ":{$colAkumPenyusutan}" . ($row - 1) . ")"
                        );
                        $sheet->setCellValue(
                            "{$colNilaiBuku}{$row}",
                            "=SUM({$colNilaiBuku}" . ($row - count($items)) . ":{$colNilaiBuku}" . ($row - 1) . ")"
                        );
                        $colTerakhir = $colNilaiBuku;

                        // format total
                        $sheet->getStyle("{$colPenyusutan}{$row}:{$colNilaiBuku}{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                        $sheet->getStyle("{$colPenyusutan}{$row}:{$colNilaiBuku}{$row}")->applyFromArray([
                            'font' => ['bold' => true],
                            'alignment' => ['vertical' => 'center'],
                            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                        ]);
                    }

                    $row += 2; // Tambahkan spasi setelah total
                }

                // Grand Total
                $row = $row-1;
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("A{$row}", "T O T A L");
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal'=>'center','vertical' => 'center'],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                ]);
                $sheet->getStyle("C{$row}:H{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['vertical' => 'center'],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                ]);

                // height
                $sheet->getRowDimension($row)->setRowHeight(25);

                $sheet->setCellValue("F{$row}", $sum_nilai_perolehan);
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');

                foreach ($items[0]['data_penyusutan'] as $key => $penyusutan) {
                    $colPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3));
                    $colAkumPenyusutan = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 1);
                    $colNilaiBuku = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 2);

                    // Menggunakan formula SUM untuk total
                    $sheet->setCellValue("{$colPenyusutan}{$row}",array_sum($sum_penyusutan[$colPenyusutan]));
                    $sheet->setCellValue("{$colAkumPenyusutan}{$row}",array_sum($sum_akum_penyusutan[$colAkumPenyusutan]));
                    $sheet->setCellValue("{$colNilaiBuku}{$row}",array_sum($sum_nilai_buku[$colNilaiBuku]));

                    $sheet->getStyle("{$colPenyusutan}{$row}:{$colNilaiBuku}{$row}")->getNumberFormat()->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"_);_(@_)');
                    $sheet->getStyle("{$colPenyusutan}{$row}:{$colNilaiBuku}{$row}")->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => ['vertical' => 'center'],
                        'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'D9E1F2']]
                    ]);
                }

                if($this->setAllBorder){
                    $styleArray = [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ];
                    $sheet->getStyle("A{$initial_row}:{$colTerakhir}{$row}")->applyFromArray($styleArray);
                }
            },
        ];
    }

    /**
     * Generate dynamic period columns.
     */
    protected function generatePeriodColumns(): array
    {
        $start = new \DateTime(substr($this->period, 0, 4) . '-01-01');
        $end = new \DateTime($this->period . '-01');
        $columns = [];

        while ($start <= $end) {
            $columns[] = $start->format('F');
            $start->modify('+1 month');
        }

        return $columns;
    }

    protected function generatePeriodColumnsHead(): array
    {
        $start = new \DateTime(substr($this->period, 0, 4) . '-01-01');
        $end = new \DateTime($this->period . '-01');
        $columns = [];

        while ($start <= $end) {
            $columns[] = $start->format('F');
            $columns[] = '';
            $columns[] = '';
            $start->modify('+1 month');
        }

        return $columns;
    }

    public function headings(): array
    {
        return [
            [get_arrangement('company_name'), '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [$this->setTitle, '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Tahun ' . $this->data['subtitle'], '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            [],
            [],
        ];
    }


    public function styles(Worksheet $sheet)
    {
        $startColumnIndex = 9;
        $datas = $this->data['data'];
        $total_group_bulan = count($datas[0]['items'][0]['data_penyusutan']);

        for ($key=0; $key < $total_group_bulan ; $key++) {
            $colNilaiBuku = Coordinate::stringFromColumnIndex($startColumnIndex + ($key * 3) + 2);
        }
        $sheet->mergeCells("A1:{$colNilaiBuku}1");
        $sheet->mergeCells("A2:{$colNilaiBuku}2");
        $sheet->mergeCells("A3:{$colNilaiBuku}3");

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_NONE,
                ],
            ],
        ];
        $sheet->getStyle("A1:A3")->applyFromArray($styleArray);

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension(3)->setRowHeight(20);

        $styles = [
            1 => ['font' => ['bold' => true]],
            'A1' => ['font' => ['bold' => true, 'size' => 22], 'alignment' => ['horizontal' => 'center']],
            'A2' => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0070C0']], 'alignment' => ['horizontal' => 'center']],
            'A3' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];


        return $styles;
    }
}
