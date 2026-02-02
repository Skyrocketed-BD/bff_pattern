<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialBalance extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'initial_balances';

    protected $primaryKey = 'id_initial_balance';

    protected $fillable = [
        'transaction_number',
        'date',
        'value',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel general_ledger
    public function toGeneralLedger()
    {
        return $this->belongsTo(GeneralLedger::class, 'id_general_ledger', 'id_general_ledger');
    }

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
