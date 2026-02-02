<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosingAdjustment extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'closing_adjustment';

    protected $primaryKey = 'id_closing_adjustment';

    protected $fillable = [
        'id_journal_adjustment',
        'transaction_number',
        'date',
        'month',
        'year',
    ];

    public function toJournalAdjustment()
    {
        return $this->belongsTo(JournalAdjustment::class, 'id_journal_adjustment', 'id_journal_adjustment');
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
