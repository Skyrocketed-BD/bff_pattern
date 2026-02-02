<?php

namespace App\Exports\Finance\ARControl;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ARControlExport implements WithMultipleSheets
{
    protected $data;
    protected $title;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->data as $journal) {
            $sheets[] = new GroupSheet($journal, $this->title);
            // break;
        }

        return $sheets;
    }
}
