<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportMenu extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'report_menu';
    
    protected $primaryKey = 'id_report_menu';

    protected $fillable = [
        'name',
        'is_annual',
        'created_by',
        'updated_by',
    ];

    // untuk relasi ke tabel report_title
    public function toReportTitle()  {
        return $this->hasMany(ReportTitle::class, 'id_report_menu', 'id_report_menu');
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
