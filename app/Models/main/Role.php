<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;
    // untuk default tabel
    protected $table = 'role';
    // untuk default primary key
    protected $primaryKey = 'id_role';
    // untuk fillable
    protected $fillable = [
        'id_role',
        'name',
    ];

    // relasi ke tabel role_access
    public function toRoleAccess()  {
        return $this->hasMany(RoleAccess::class, 'id_role', 'id_role');
    }

    protected function casts(): array
    {
        return [
            'id_role' => 'integer',
        ];
    }

}
