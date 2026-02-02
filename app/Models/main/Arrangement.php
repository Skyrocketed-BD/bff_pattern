<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arrangement extends Model
{
    use HasFactory;

    protected $table = 'arrangements';

    protected $primaryKey = 'id_arrangement';

    protected $fillable = [
        'key',
        'type',
        'value',
    ];
}
