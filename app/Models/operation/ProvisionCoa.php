<?php

namespace App\Models\operation;

use App\Models\finance\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvisionCoa extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'provision_coa';

    // primary key
    protected $primaryKey = 'id_provision_coa';

    // fillable
    protected $fillable = [
        'id_provision',
        'id_journal',
        'no_invoice',
        'method_coa',
        'attachment',
        'attachment_pnbp_final',
        'date',
        'price',
        'pay_pnbp',
        'hma',
        'hpm',
        'kurs',
        'ni_final',
        'fe_final',
        'co_final',
        'sio2_final',
        'mgo2_final',
        'mc_final',
        'tonage_final',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ni_final'     => 'float',
        'fe_final'     => 'float',
        'co_final'     => 'float',
        'sio2_final'   => 'float',
        'mgo2_final'   => 'float',
        'mc_final'     => 'float',
        'tonage_final' => 'float'
    ];

    // relasi to provision table
    public function toProvision()
    {
        return $this->belongsTo(Provision::class, 'id_provision', 'id_provision');
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
