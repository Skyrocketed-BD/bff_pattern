<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosingDepreciation extends Model
{
    use HasFactory;

    // specific connection database 
    protected $connection = 'finance';

    protected $table = 'closing_depreciations';

    protected $primaryKey = 'id_closing_depreciation';

    protected $fillable = [
        'transaction_number',
        'month',
        'year',
        'created_by',
        'updated_by',
    ];

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
