<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionTerm extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'transaction_term';

    protected $primaryKey = 'id_transaction_term';

    protected $fillable = [
        'id_transaction',
        'id_receipt',
        'invoice_number',
        'nama',
        'date',
        'percent',
        'value_ppn',
        'value_pph',
        'value_percent',
        'value_deposit',
        'deposit',
        'final',
        'created_by',
        'updated_by',
    ];

    public function toTransactionBill()
    {
        return $this->belongsTo(TransactionBill::class, 'id_transaction_bill', 'id_transaction_bill');
    }

    public function toTransaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction', 'id_transaction');
    }

    public function toReceipt()
    {
        return $this->belongsTo(Receipts::class, 'id_receipt', 'id_receipt');
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
