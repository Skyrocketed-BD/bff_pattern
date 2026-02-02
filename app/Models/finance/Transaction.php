<?php

namespace App\Models\finance;

use App\Models\main\Kontak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'transaction';

    protected $primaryKey = 'id_transaction';

    protected $fillable = [
        'id_kontak',
        'id_journal',
        'id_invoice_bill',
        'transaction_number',
        'from_or_to',
        'date',
        'value',
        'description',
        'attachment',
        'reference_number',
        'category',
        'in_ex',
        'status',
        'created_by',
        'updated_by',
    ];

    // untuk relasi
    protected $with = ['toJournal', 'toReceipts', 'toExpenditure', 'toTransactionTax', 'toTransactionTerm', 'toInvoiceBill'];

    // untuk relasi ke tabel kontak
    public function toKontak()
    {
        return $this->setConnection('mysql')->belongsTo(Kontak::class, 'id_kontak', 'id_kontak');
    }
    
    // untuk relasi ke tabel journal
    public function toJournal()
    {
        return $this->belongsTo(Journal::class, 'id_journal', 'id_journal');
    }

    // untuk relasi ke tabel receipts
    public function toReceipts()
    {
        return $this->hasMany(Receipts::class, 'reference_number', 'reference_number');
    }

    // untuk relasi ke tabel expenditure
    public function toExpenditure()
    {
        return $this->hasMany(Expenditure::class, 'reference_number', 'reference_number');
    }

    // untuk relasi ke tabel transaction tax
    public function toTransactionTax()
    {
        return $this->hasMany(TransactionTax::class, 'transaction_number', 'transaction_number');
    }

    // untuk relasi ke tabel transaction term
    public function toTransactionTerm()
    {
        return $this->hasMany(TransactionTerm::class, 'id_transaction', 'id_transaction');
    }

    // untuk relasi ke tabel invoice bill
    public function toInvoiceBill()
    {
        return $this->belongsTo(InvoiceBill::class, 'id_invoice_bill', 'id_invoice_bill');
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
