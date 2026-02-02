<?php

namespace App\Exports\Operation;

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

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OreShippingExport implements WithMultipleSheets
{

    protected $data;
    public $periode;
    public $title;
    public $type;


    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function setType($type)
	{
		$this->type = $type;
		return $this;
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


    public function sheets(): array
    {
        $sheets = [];

        if ($this->type == 'month') {
            $sheets = [
                new OreShippingDetailSheet($this->data, $this->title, $this->periode),
                new OreShippingSummaryByContractorSheet($this->data, $this->periode),
            ];
        } else {
            $sheets = [
                new OreShippingDetailSheet($this->data, $this->title, $this->periode),
                new OreShippingSummaryByContractorSheet($this->data, $this->periode),
                new OreShippingSummaryByMonthSheet($this->data, $this->title, $this->periode),
            ];
        }
        return $sheets;
    }
}
