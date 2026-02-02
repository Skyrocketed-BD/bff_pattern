<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'tax_rate';

    protected $primaryKey = 'id_tax_rate';

    protected $fillable = [
        'id_tax',
        'kd_tax',
        'name',
        'rate',
        'ref',
        'count',
        'effective_date',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel tax
    protected $with = ['toTax'];

    // relasi ke tabel tax
    public function toTax()
    {
        return $this->belongsTo(Tax::class, 'id_tax', 'id_tax');
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
