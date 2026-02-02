<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kurs extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'kurs';

    protected $primaryKey = 'id_kurs';

    protected $fillable = [
        'date',
        'jual',
        'beli',
        'tengah',
    ];
}
