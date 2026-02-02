<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCoa extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'asset_coa';

    protected $primaryKey = 'id_asset_coa';

    protected $fillable = [
        'name',
        'id_coa',
        'id_coa_acumulated',
        'id_coa_expense',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel coa
    public function toCoa()
    {
        return $this->belongsTo(Coa::class, 'id_coa', 'id_coa');
    }

    // relasi ke tabel asset_head
    public function toAssetHead()
    {
        return $this->hasMany(AssetHead::class, 'id_asset_coa', 'id_asset_coa');
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
