<?php

namespace App\Models\operation;

use App\Models\main\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingInstructionApprove extends Model
{
    use HasFactory;

    // spesific connection database
    protected $connection = 'operation';

    // table name
    protected $table = 'shipping_instruction_approve';

    // primary key
    protected $primaryKey = 'id_shipping_instruction_approve';

    // fillable
    protected $fillable = [
        'id_shipping_instruction',
        'id_users',
        'date',
        'status',
        'reject_reason'
    ];

    // relasi ke tabel user
    public function toUser()  {
        return $this->setConnection('mysql')->belongsTo(User::class, 'id_users', 'id_users');
    }
}
