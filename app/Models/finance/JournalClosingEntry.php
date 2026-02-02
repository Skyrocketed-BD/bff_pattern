<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalClosingEntry extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'journal_closing_entries';

    protected $primaryKey = 'id_journal_closing_entry';

    protected $fillable = [
        'transaction_number',
        'date',
        'value',
        'description',
        'created_by',
        'updated_by',
    ];

    public function toJournalClosingEntrySet()
    {
        return $this->hasMany(JournalClosingEntrySets::class, 'id_journal_closing_entry', 'id_journal_closing_entry');
    }

    public function scopeWhereBetweenMonth($query, string $start_date, string $end_date)
    {
        return $query->whereBetween('date', [$start_date, $end_date]);
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
