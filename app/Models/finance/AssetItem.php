<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetItem extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'asset_item';

    protected $primaryKey = 'id_asset_item';

    protected $fillable = [
        'id_asset_head',
        'asset_number',
        'identity_number',
        'qty',
        'price',
        'total',
        'attachment',
        'disposal',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel asset_head
    public function toAssetHead()
    {
        return $this->belongsTo(AssetHead::class, 'id_asset_head', 'id_asset_head');
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
