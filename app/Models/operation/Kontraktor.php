<?php

namespace App\Models\operation;

use App\Models\main\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kontraktor extends Model
{
    use HasFactory;
    // specific connection database
    protected $connection = 'operation';

    // untuk default tabel
    protected $table = 'kontraktor';

    // untuk default primary key
    protected $primaryKey = 'id_kontraktor';

    // untuk fillable
    protected $fillable = [
        'id_kontraktor',
        'company',
        'leader',
        'npwp',
        'telepon',
        'address',
        'postal_code',
        'email',
        'website',
        'initial',
        'color',
        'capital',
        'created_by',
        'updated_by'
    ];

    protected function casts(): array
    {
        return [
            'id_kontraktor' => 'integer',
        ];
    }

    // relasi ke tabel block
    public function toBlock()  {
        return $this->hasMany(Block::class, 'id_kontraktor', 'id_kontraktor');
    }

    // relasi ke tabel pit
    public function toPit()  {
        return $this->hasMany(Pit::class, 'id_kontraktor', 'id_kontraktor');
    }

    // relasi ke tabel dom_in_pit
    public function toDomInPit()  {
        return $this->hasMany(DomInPit::class, 'id_kontraktor', 'id_kontraktor');
    }

    // relasi ke tabel shipping_instruction
    public function toShippingInstruction()  {
        return $this->hasMany(ShippingInstruction::class, 'id_kontraktor', 'id_kontraktor');
    }

    public function toUser() {
        return $this->setConnection('mysql')->belongsTo(User::class, 'id_kontraktor','id_kontraktor');
    }

    public function scopeWhereKontraktor($query, string $id_kontraktor)
    {
        return $query->where('id_kontraktor', $id_kontraktor);
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
