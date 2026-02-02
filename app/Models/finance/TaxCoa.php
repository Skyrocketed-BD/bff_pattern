<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxCoa extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'tax_coa';

    protected $primaryKey = 'id_tax_coa';

    protected $fillable = [
        'id_tax',
        'id_coa',
        'created_by',
        'updated_by',
    ];

    // relasi
    protected $with = ['toTax', 'toCoa'];

    // relasi ke tabel tax
    public function toTax()
    {
        return $this->belongsTo(Tax::class, 'id_tax', 'id_tax');
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
