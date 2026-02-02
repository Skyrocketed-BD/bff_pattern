<?php

namespace App\Exports\GeneralLedger;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GeneralLedgerExport implements WithMultipleSheets
{
    protected $data;
    protected $periode;

    public function __construct($data)
    {
        $this->data = $data; // Data yang sudah diproses dan dikelompokkan sebelumnya
    }

    public function setPeriode($periode)
	{
		$this->periode = $periode;
	}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->data as $journal) {
            $sheets[] = new GroupSheet($journal, $this->periode);
            // break;
        }

        return $sheets;
    }
}
