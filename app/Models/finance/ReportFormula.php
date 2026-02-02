<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportFormula extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'report_formula';

    protected $primaryKey = 'id_report_formula';

    protected $fillable = [
        'id_report_title',
        'id_report_title_select',
        'operation',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id_report_title' => 'integer',
            'id_report_title_select' => 'integer',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($row) {
            $row->created_by = auth('api')->user()->id_users;
        });

        static::updating(function ($row) {
            $row->updated_by = auth('api')->user()->id_users;
        });
    }
}
