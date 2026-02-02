<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    use HasFactory;
    // untuk default tabel
    protected $table = 'menu_category';
    // untuk default primary key
    protected $primaryKey = 'id_menu_category';
    // untuk fillable
    protected $fillable = [
        'id_menu_category',
        'id_menu_module',
        'name',
    ];

    // relasi ke tabel menu_module
    public function toMenuModule()  {
        return $this->belongsTo(MenuModule::class, 'id_menu_module', 'id_menu_module');
    }

    // relasi ke tabel menu_body
    public function toMenuBody()  {
        return $this->hasMany(MenuBody::class, 'id_menu_category', 'id_menu_category');
    }

    protected function casts(): array
    {
        return [
            'id_menu_category' => 'integer',
            'id_menu_module' => 'integer',
        ];
    }
}
