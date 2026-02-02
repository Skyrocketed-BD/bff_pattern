<?php

namespace App\Models\finance;

use App\Models\main\Kontak;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceBill extends Model
{
    use HasFactory;

    protected $connection = 'finance';

    protected $table = 'invoice_bills';

    protected $primaryKey = 'id_invoice_bill';

    protected $fillable = [
        'id_kontak',
        'id_journal',
        'transaction_number',
        'reference_number',
        'inv_date',
        'due_date',
        'total',
        'description',
        'category',
        'type',
        'in_ex',
        'payment_status',
        'is_outstanding',
        'is_actual'
    ];

    public function getPaymentTimeStatusAttribute()
    {
        $currentDate = Carbon::today();
        $dueDate = Carbon::parse($this->due_date);

        // Over Due: sudah lewat deadline dan belum lunas
        if ($currentDate->greaterThan($dueDate) && in_array($this->payment_status, ['issued', 'partial'])) {
            return 'Over Due';
        }

        // On Time: dibayar sebelum atau tepat deadline
        if ($this->payment_status === 'paid' && $this->inv_date && Carbon::parse($this->inv_date)->lessThanOrEqualTo($dueDate)) {
            return 'On Time';
        }

        // Late Payment: dibayar tapi lewat deadline
        if ($this->payment_status === 'paid' && $this->inv_date && Carbon::parse($this->inv_date)->greaterThan($dueDate)) {
            return 'Late Payment';
        }

        // Waiting: belum lewat deadline dan belum dibayar
        if ($currentDate->lessThanOrEqualTo($dueDate) && in_array($this->payment_status, ['issued', 'partial'])) {
            return 'Waiting';
        }

        return 'Unknown';
    }

    public function toInvoiceBillDetail()
    {
        return $this->hasMany(InvoiceBillDetail::class, 'id_invoice_bill', 'id_invoice_bill');
    }

    public function toTransaction()
    {
        return $this->belongsTo(Transaction::class, 'id_invoice_bill', 'id_invoice_bill');
    }

    public function toKontak()
    {
        return $this->setConnection('mysql')->belongsTo(Kontak::class, 'id_kontak', 'id_kontak');
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
