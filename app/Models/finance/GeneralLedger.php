<?php

namespace App\Models\finance;

use App\Models\main\Kontak;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralLedger extends Model
{
    use HasFactory, SoftDeletes;

    // specific connection database
    protected $connection = 'finance';

    protected $table = 'general_ledgers';

    protected $primaryKey = 'id_general_ledger';

    protected $fillable = [
        'id_kontak',
        'id_journal',
        'transaction_number',
        'date',
        'coa',
        'type',
        'value',
        'description',
        'reference_number',
        'phase',
        'calculated',
        'deleted_at',
        'created_by',
        'updated_by',
    ];

    // relasi ke tabel coa
    public function toCoa()
    {
        return $this->belongsTo(Coa::class, 'coa', 'coa');
    }

    public function toJournal()
    {
        return $this->belongsTo(Journal::class, 'id_journal', 'id_journal');
    }

    public function toTransactionFull()
    {
        return $this->belongsTo(TransactionFull::class, 'transaction_number', 'transaction_number');
    }

    public function toTransaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_number', 'transaction_number');
    }

    public function toJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'transaction_number', 'transaction_number');
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
