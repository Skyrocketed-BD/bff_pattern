<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportBody extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'report_body';

    protected $primaryKey = 'id_report_body';

    protected $fillable = [
        'id_report_title',
        'id_report_menu',
        'id_coa_body',
        'id_coa',
        'method',
        'operation',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel coa
    public function toCoa()
    {
        return $this->belongsTo(Coa::class, 'id_coa', 'id_coa');
    }

    public function toCoaBody()
    {
        return $this->belongsTo(Coa::class, 'id_coa_body', 'id_coa_body');
    }

    public function toReportTitle()
    {
        return $this->belongsTo(ReportTitle::class, 'id_report_title', 'id_report_title');
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
