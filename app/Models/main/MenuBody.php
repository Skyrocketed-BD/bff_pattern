<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuBody extends Model
{
    use HasFactory;
    // untuk default tabel
    protected $table = 'menu_body';
    // untuk default primary key
    protected $primaryKey = 'id_menu_body';
    // untuk fillable
    protected $fillable = [
        'id_menu_body',
        'id_menu_category',
        'name',
        'icon',
        'url',
        'position',
        'is_enabled',
    ];

    // relasi ke tabel menu_category
    public function toMenuCategory()  {
        return $this->belongsTo(MenuCategory::class, 'id_menu_category', 'id_menu_category');
    }

    // relasi ke tabel menu_child
    public function toMenuChild()  {
        return $this->hasMany(MenuChild::class, 'id_menu_body', 'id_menu_body');
    }

    protected function casts(): array
    {
        return [
            'id_menu_body' => 'integer',
            'id_menu_category' => 'integer',
            // 'is_enabled' => 'integer',
        ];
    }

}
