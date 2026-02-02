<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetHead extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'asset_head';

    protected $primaryKey = 'id_asset_head';

    protected $fillable = [
        'id_asset_coa',
        'id_asset_group',
        'id_asset_category',
        'id_transaction',
        'id_transaction_full',
        'name',
        'tgl',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel asset_coa
    public function toAssetCoa()
    {
        return $this->belongsTo(AssetCoa::class, 'id_asset_coa', 'id_asset_coa');
    }

    // relasi ke tabel asset_group
    public function toAssetGroup()
    {
        return $this->belongsTo(AssetGroup::class, 'id_asset_group', 'id_asset_group');
    }

    // relasi ke tabel asset_category
    public function toAssetCategory()
    {
        return $this->belongsTo(AssetCategory::class, 'id_asset_category', 'id_asset_category');
    }

    // relasi ke tabel asset_item
    public function toAssetItem()
    {
        return $this->hasMany(AssetItem::class, 'id_asset_head', 'id_asset_head');
    }

    public function toTransaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction', 'id_transaction');
    }

    public function toTransactionFull()
    {
        return $this->belongsTo(TransactionFull::class, 'id_transaction_full', 'id_transaction_full');
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
