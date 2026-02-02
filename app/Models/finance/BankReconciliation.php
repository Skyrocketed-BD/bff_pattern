<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'bank_reconciliation';

    protected $primaryKey = 'id_reconcile';

    protected $fillable = [
        'id_coa_bank',
        'transaction_number',
        'date',
        'bank_interest',
        'bank_interest_tax',
        'value',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bank_interest'     => 'float',
        'bank_interest_tax' => 'float',
        'value'             => 'float',
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
