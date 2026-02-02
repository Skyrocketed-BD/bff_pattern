<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDisposalItem extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'asset_disposal_item';

    protected $primaryKey = 'id_asset_disposal_item';

    protected $fillable = [
        'id_asset_disposal',
        'id_asset_item',
        'purchase_price',
        'book_value',
        'selling_price',
        'created_by',
        'updated_by',
    ];

    public function toAssetDisposal()
    {
        return $this->belongsTo(AssetDisposal::class, 'id_asset_disposal', 'id_asset_disposal');
    }
    
    public function toAssetItem()
    {
        return $this->belongsTo(AssetItem::class, 'id_asset_item', 'id_asset_item');
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
