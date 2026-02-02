<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosingEntry extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'closing_entries';

    protected $primaryKey = 'id_closing_entry';

    protected $fillable = [
        'transaction_number',
        'month',
        'year',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel closing_entry_detail
    public function toClosingEntryDetail()
    {
        return $this->hasMany(ClosingEntryDetail::class, 'id_closing_entry', 'id_closing_entry');
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
