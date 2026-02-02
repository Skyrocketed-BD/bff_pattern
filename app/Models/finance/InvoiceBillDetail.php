<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceBillDetail extends Model
{
    use HasFactory;

    protected $connection = 'finance';

    protected $table = 'invoice_bill_details';

    protected $primaryKey = 'id_invoice_bill_detail';

    protected $fillable = [
        'id_invoice_bill',
        'coa',
        'amount',
        'type'
    ];

    protected $with = ['toCoa'];

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
