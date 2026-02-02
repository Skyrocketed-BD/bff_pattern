<?php

namespace App\Models\finance;

use App\Models\main\Kontak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionFull extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'transaction_full';

    protected $primaryKey = 'id_transaction_full';

    protected $fillable = [
        'id_kontak',
        'id_journal',
        'id_invoice_bill',
        'transaction_number',
        'invoice_number',
        'efaktur_number',
        'date',
        'from_or_to',
        'value',
        'description',
        'attachment',
        'category',
        'record_type',
        'in_ex',
        'status',
        'created_by',
        'updated_by',
    ];

    // query scope
    public function scopeWhereBetweenMonth($query, string $start_date, string $end_date)
    {
        return $query->whereBetween('date', [$start_date, $end_date]);
    }

    public function toKontak()
    {
        return $this->setConnection('mysql')->belongsTo(Kontak::class, 'id_kontak', 'id_kontak');
    }

    public function toJournal()
    {
        return $this->setConnection('finance')->belongsTo(Journal::class, 'id_journal', 'id_journal');
    }

    public function toInvoiceBill()
    {
        return $this->setConnection('finance')->belongsTo(InvoiceBill::class, 'id_invoice_bill', 'id_invoice_bill');
    }

    public function toTransactionTax()
    {
        return $this->hasMany(TransactionTax::class, 'transaction_number', 'transaction_number');
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
