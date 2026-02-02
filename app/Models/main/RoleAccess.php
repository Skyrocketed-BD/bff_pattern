<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleAccess extends Model
{
    use HasFactory;
    // untuk default tabel
    protected $table = 'role_access';
    // untuk default primary key
    protected $primaryKey = 'id_role_access';
    // untuk fillable
    protected $fillable = [
        'id_role_access',
        'id_menu_module',
        'id_menu_body',
        'id_role',
        'action',
    ];

    // relasi ke tabel menu_body
    public function toMenuBody()  {
        return $this->belongsTo(MenuBody::class, 'id_menu_body', 'id_menu_body');
    }

    // relasi ke tabel role
    public function toRole()  {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    protected function casts(): array
    {
        return [
            'id_role_access' => 'integer',
            'id_role'        => 'integer',
            'id_menu_body'   => 'integer',
        ];
    }
}
