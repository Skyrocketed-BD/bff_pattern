<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownPaymentDetails extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'down_payment_details';

    protected $primaryKey = 'id_down_payment_detail';

    protected $fillable = [
        'id_down_payment',
        'id_invoice_bill',
        'category',
        'transaction_number',
        'date',
        'value',
        'description',
        'status',
        'attachment'
    ];

    public function toDownPayment()
    {
        return $this->belongsTo(DownPayment::class, 'id_down_payment', 'id_down_payment');
    }

    public function toInvoiceBill()
    {
        return $this->belongsTo(InvoiceBill::class, 'id_invoice_bill', 'id_invoice_bill');
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
