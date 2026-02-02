<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'asset_category';

    protected $primaryKey = 'id_asset_category';

    protected $fillable = [
        'name',
        'presence',
        'is_depreciable',
        'created_by',
        'updated_by',
    ];

    // relasi ke AssetHead
    public function toAssetHead()
    {
        return $this->hasMany(AssetHead::class, 'id_asset_category');
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
