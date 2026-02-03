<?php

namespace App\Models\finance;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoaGroup extends Model
{
    use HasFactory, Loggable;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'coa_group';

    protected $primaryKey = 'id_coa_group';

    protected $fillable = [
        'name',
        'coa',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel coa_head
    public function toCoaHead()
    {
        return $this->hasMany(CoaHead::class, 'id_coa_group', 'id_coa_group');
    }

    /**
     * Custom log prefix (opsional, jika mau custom)
     */
    public function getLogPrefix(): string
    {
        return 'finance:coa_group';
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
