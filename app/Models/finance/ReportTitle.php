<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTitle extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'report_title';

    protected $primaryKey = 'id_report_title';

    protected $fillable = [
        'id_report_menu',
        'name',
        'type',
        'value',
        'display_currency',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'id_report_title' => 'integer',
        ];
    }

    // relasi ke tabel report_menu
    public function toReportMenu()  {
        return $this->belongsTo(ReportMenu::class, 'id_report_menu', 'id_report_menu');
    }

    // relasi ke tabel report_body
    public function toReportBody()  {
        return $this->hasMany(ReportBody::class, 'id_report_title', 'id_report_title');
    }

    // relasi ke tabel report_formula
    public function toReportFormula()  {
        return $this->hasMany(ReportFormula::class, 'id_report_title', 'id_report_title');
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
