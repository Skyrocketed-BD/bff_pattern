<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'asset_disposal';

    protected $primaryKey = 'id_asset_disposal';

    protected $fillable = [
        'id_transaction',
        'id_transaction_full',
        'date',
        'description',
        'attachment',
        'status',
        'created_by',
        'updated_by',
    ];

    public function toAssetDisposalItems()
    {
        return $this->hasMany(AssetDisposalItem::class, 'id_asset_disposal', 'id_asset_disposal');
    }

    public function toTransaction()
    {
        return $this->belongsTo(Transaction::class, 'id_transaction', 'id_transaction');
    }

    public function toTransactionFull()
    {
        return $this->belongsTo(TransactionFull::class, 'id_transaction_full', 'id_transaction');
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
