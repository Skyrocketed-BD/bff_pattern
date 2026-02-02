<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionTax extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'transaction_tax';

    protected $primaryKey = 'id_transaction_tax';

    protected $fillable = [
        'transaction_number',
        'id_coa',
        'id_tax',
        'id_tax_rate',
        'rate',
        'created_by',
        'updated_by',
    ];
    
    // relasi ke tabel tax rate
    public function toTaxRate()
    {
        return $this->belongsTo(TaxRate::class, 'id_tax_rate', 'id_tax_rate');
    }

    // relasi ke tabel coa
    public function toCoa()
    {
        return $this->belongsTo(Coa::class, 'id_coa', 'id_coa');
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
