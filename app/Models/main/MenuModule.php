<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuModule extends Model
{
    use HasFactory;
    // untuk default tabel
    protected $table = 'menu_module';
    // untuk default primary key
    protected $primaryKey = 'id_menu_module';
    // untuk fillable
    protected $fillable = [
        'id_menu_module',
        'name',
    ];

    // relasi ke tabel menu_category
    public function toMenuCategory()  {
        return $this->hasMany(MenuCategory::class, 'id_menu_module', 'id_menu_module');
    }

    protected function casts(): array
    {
        return [
            'id_menu_module' => 'integer'
        ];
    }

}
