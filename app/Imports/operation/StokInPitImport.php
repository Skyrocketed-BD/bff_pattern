<?php

namespace App\Imports\operation;

use App\Models\operation\StokInPit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StokInPitImport implements ToModel, WithStartRow, WithHeadingRow, WithValidation
{
    use Importable;

    private $request;
    private $id_kontraktor;
    protected static $hasEmptyRow = false;

    public function __construct(Request $request, $id_kontraktor)
    {
        $this->request       = $request;
        $this->id_kontraktor = $id_kontraktor;
    }
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        // Check if the row is empty
        if (empty(array_filter($row))) {
            self::$hasEmptyRow = true; // Mark that an empty row exists
            return null;
        }

        $data = [
            'id_kontraktor' => $this->id_kontraktor,
            'id_block'      => $this->request->id_block,
            'id_pit'        => $this->request->id_pit,
            'id_dom_in_pit' => $this->request->id_dom_in_pit,
            'date'          => $this->request->date,
            'sample_id'     => $row['sample_id'],
            'ni'            => $row['ni'],
            'fe'            => $row['fe'],
            'co'            => $row['co'],
            'sio2'          => $row['sio2'],
            'mgo2'          => $row['mgo2'],
            'tonage'        => $row['jumlah_ore'],
            'ritasi'        => $row['ritasi'],
            'created_by'    => auth('api')->user()->id_users,
        ];

        return StokInPit::create($data);
    }

    public function rules(): array
    {
        return [
            'sample_id'  => ['required', 'unique:operation.stok_in_pits,sample_id'],
            'ni'         => ['required', 'numeric', 'gt:0'],
            'fe'         => ['required', 'numeric', 'gt:0'],
            'co'         => ['required', 'numeric'],
            'sio2'       => ['required', 'numeric', 'gt:0'],
            'mgo2'       => ['required', 'numeric', 'gt:0'],
            'jumlah_ore' => ['required', 'numeric'],
            'ritasi'     => ['required', 'numeric'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'sample_id.required'  => 'Sample Id wajib diisi',
            'sample_id.unique'    => 'Sample Id (:input) sudah ada',
            'ni.required'         => 'Ni wajib diisi',
            'ni.numeric'          => 'Ni harus berupa angka',
            'ni.gt'               => 'Ni harus lebih besar dari 0',
            'fe.required'         => 'Fe wajib diisi',
            'fe.numeric'          => 'Fe harus berupa angka',
            'fe.gt'               => 'Fe harus lebih besar dari 0',
            'co.required'         => 'Co wajib diisi',
            'co.numeric'          => 'Co harus berupa angka',
            'co.gt'               => 'Co harus lebih besar dari 0',
            'sio2.required'       => 'Sio2 wajib diisi',
            'sio2.numeric'        => 'Sio2 harus berupa angka',
            'sio2.gt'             => 'Sio2 harus lebih besar dari 0',
            'mgo2.required'       => 'Mgo2 wajib diisi',
            'mgo2.numeric'        => 'Mgo2 harus berupa angka',
            'mgo2.gt'             => 'Mgo2 harus lebih besar dari 0',
            'jumlah_ore.required' => 'Jumlah Ore wajib diisi',
            'jumlah_ore.numeric'  => 'Jumlah Ore harus berupa angka',
            'ritasi.required'     => 'Ritasi wajib diisi',
            'ritasi.numeric'      => 'Ritasi harus berupa angka',
        ];
    }

    public static function hasEmptyRow()
    {
        return self::$hasEmptyRow;
    }
}
