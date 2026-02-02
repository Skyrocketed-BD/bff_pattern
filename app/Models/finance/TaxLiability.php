<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxLiability extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'tax_liability';

    protected $primaryKey = 'id_tax_liability';

    protected $fillable = [
        'id_coa',
        'transaction_number',
        'date',
        'value',
        'description',
        'attachment',
        'status',
        'created_by',
        'updated_by',
    ];

    public function toCoa()  {
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
