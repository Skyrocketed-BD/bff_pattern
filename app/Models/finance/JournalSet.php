<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalSet extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'journal_set';

    protected $primaryKey = 'id_journal_set';

    protected $fillable = [
        'id_tax_rate',
        'id_journal',
        'id_coa',
        'type',
        'open_input',
        'serial_number',
        'created_by',
        'updated_by',
    ];


    protected function casts(): array
    {
        return [
            'id_journal' => 'integer',
            'id_coa'    => 'integer',
        ];
    }

    // relasi ke tabel journal
    public function toJournal()  {
        return $this->belongsTo(Journal::class, 'id_journal', 'id_journal');
    }

    // relasi ke tabel coa
    public function toCoa()  {
        return $this->belongsTo(Coa::class, 'id_coa', 'id_coa');
    }

    // relasi ke tabel tax_rate
    public function toTaxRate()  {
        return $this->belongsTo(TaxRate::class, 'id_tax_rate', 'id_tax_rate');
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
