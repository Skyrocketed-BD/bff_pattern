<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedgerLog extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'general_ledger_logs';

    protected $primaryKey = 'id_general_ledger_log';

    protected $fillable = [
        'transaction_number',
        'date',
        'coa',
        'type',
        'value',
        'description',
        'reference_number',
        'revision',
        'created_by',
        'updated_by'
    ];

    public function toCoa()
    {
        return $this->belongsTo(Coa::class, 'coa', 'coa');
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
