<?php

namespace App\Models\finance;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use HasFactory, Loggable;

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

    public function toAssetHead()
    {
        return $this->hasMany(AssetHead::class, 'id_asset_category');
    }

    /**
     * Custom log prefix (opsional, jika mau custom)
     */
    public function getLogPrefix(): string
    {
        return 'finance:asset_category';
    }

    /**
     * Custom data yang akan di-log (opsional)
     */
    // public function getLogData(): array
    // {
    //     return [];
    // }

    /**
     * Mengisi created_by dan updated_by secara otomatis berdasarkan user yang login
     */
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
