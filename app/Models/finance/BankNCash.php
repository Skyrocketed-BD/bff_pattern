<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankNCash extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'bank_n_cash';

    protected $primaryKey = 'id_bank_n_cash';

    protected $fillable = [
        'id_coa',
        'type',
        'show',
        'created_by',
        'updated_by',
    ];

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
