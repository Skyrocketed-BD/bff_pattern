<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\main\Kontak;

class DownPayment extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'down_payments';

    protected $primaryKey = 'id_down_payment';

    protected $fillable = [
        'id_kontak',
    ];

    public function toDownPaymentDetail()
    {
        return $this->hasMany(DownPaymentDetails::class, 'id_down_payment', 'id_down_payment');
    }

    public function toKontak()
    {
        return $this->setConnection('mysql')->belongsTo(Kontak::class, 'id_kontak', 'id_kontak');
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
