<?php

namespace App\Models\finance;

use App\Models\main\Kontak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipts extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'receipts';

    protected $primaryKey = 'id_receipt';

    protected $fillable = [
        'id_kontak',
        'id_journal',
        'transaction_number',
        'date',
        'receive_from',
        'pay_type',
        'record_type',
        'value',
        'description',
        'attachment',
        'reference_number',
        'in_ex',
        'status',
        'created_by',
        'updated_by',
    ];

    // relasi
    protected $with = ['toJournal'];

    // relasi ke tabel journal
    public function toJournal()
    {
        return $this->belongsTo(Journal::class, 'id_journal', 'id_journal');
    }

    public function toTransaction()
    {
        return $this->belongsTo(Transaction::class, 'reference_number', 'reference_number');
    }

    public function toKontak()
    {
        return $this->setConnection('mysql')->belongsTo(Kontak::class, 'id_kontak', 'id_kontak');
    }

    // query scope
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

        static::updated(function ($row) {
            $transaction_number    = $row->transaction_number;
            $reference_number      = $row->reference_number;
            $transaction_number_dp = "DP-" . $row->transaction_number;

            // untuk delete down payment details
            $check_down_payment_details = DownPaymentDetails::where('transaction_number', $transaction_number_dp)->first();
            if ($check_down_payment_details) {
                $check_down_payment_details->status = 'deleted';
                $check_down_payment_details->save();
            }

            // untuk delete liability details
            $check_advance_payment = LiabilityDetail::where('transaction_number', $transaction_number_dp)->first();
            if ($check_advance_payment) {
                $check_advance_payment->status = 'deleted';
                $check_advance_payment->save();
            }

            // untuk check transaction term
            $check_transaction_term = TransactionTerm::where('id_receipt', $row->id_receipt)->first();
            if ($check_transaction_term) {
                $check_transaction_term->id_receipt = null;
                $check_transaction_term->save();
            }

            // untuk delete general ledger
            delete_general_ledger($transaction_number);

            // untuk update invoice bill
            _update_invoice_bill($reference_number);
        });
    }
}
