<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Switching extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'switching';

    protected $primaryKey = 'id_switching';

    protected $fillable = [
        'transaction_number',
        'date',
        'value',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function scopeWhereBetweenMonth($query, string $start_date, string $end_date)
    {
        return $query->whereBetween('date', [$start_date, $end_date]);
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
