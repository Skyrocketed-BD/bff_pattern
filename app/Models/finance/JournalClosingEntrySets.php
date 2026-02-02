<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalClosingEntrySets extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'journal_closing_entry_sets';

    protected $primaryKey = 'id_journal_closing_entry_set';

    protected $fillable = [
        'id_journal_closing_entry',
        'id_coa',
        'type',
        'value',
        'serial_number',
        'created_by',
        'updated_by',
    ];

    public function toCoa()
    {
        return $this->belongsTo(Coa::class, 'id_coa', 'id_coa');
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
