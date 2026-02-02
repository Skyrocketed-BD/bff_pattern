<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'coa';

    protected $primaryKey = 'id_coa';

    protected $fillable = [
        'id_coa_body',
        'name',
        'coa',
        'normal_balance',
        'created_by',
        'updated_by',
    ];

    // relasi
    protected $with = ['toCoaBody'];

    // relasi ke tabel coa_body
    public function toCoaBody()
    {
        return $this->belongsTo(CoaBody::class, 'id_coa_body', 'id_coa_body');
    }

    // relasi ke tabel general_ledger
    public function toGeneralLedger()
    {
        return $this->hasMany(GeneralLedger::class, 'coa', 'coa');
    }

    // relasi ke tabel tax_coa
    public function toTaxCoa()
    {
        return $this->belongsTo(TaxCoa::class, 'id_coa', 'id_coa');
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
