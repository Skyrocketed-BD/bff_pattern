<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalAdjustment extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'journal_adjustments';

    protected $primaryKey = 'id_journal_adjustment';

    protected $fillable = [
        'transaction_number',
        'date',
        'value',
        'description',
        'transaction_type',
        'duration',
        'remaining',
        'reference_number',
        'status'
    ];

    // relasi ke tabel journal_adjustment_set
    public function toJournalAdjustmentSet()
    {
        return $this->hasMany(JournalAdjustmentSet::class, 'id_journal_adjustment', 'id_journal_adjustment');
    }

    public function toClosingAdjustment()
    {
        return $this->hasMany(ClosingAdjustment::class, 'id_journal_adjustment', 'id_journal_adjustment');
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
