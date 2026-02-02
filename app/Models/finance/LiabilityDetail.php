<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiabilityDetail extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'liability_details';

    protected $primaryKey = 'id_liability_detail';

    protected $fillable = [
        'id_liability',
        'id_invoice_bill',
        'category',
        'transaction_number',
        'date',
        'value',
        'description',
        'status',
        'attachment'
    ];

    public function toLiability()
    {
        return $this->belongsTo(Liability::class, 'id_liability', 'id_liability');
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
