<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoaClasification extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'coa_clasification';

    protected $primaryKey = 'id_coa_clasification';

    protected $fillable = [
        'name',
        'normal_balance',
        'group',
        'accrual',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel coa_body
    public function toCoaBody()
    {
        return $this->hasMany(CoaBody::class, 'id_coa_clasification', 'id_coa_clasification');
    }
}
