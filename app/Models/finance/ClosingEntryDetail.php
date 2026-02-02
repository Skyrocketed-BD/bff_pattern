<?php

namespace App\Models\finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosingEntryDetail extends Model
{
    use HasFactory;

    // specific connection database
    protected $connection = 'finance';

    // table name
    protected $table = 'closing_entry_details';

    // primary key
    protected $primaryKey = 'id_closing_entry_detail';

    protected $fillable = [
        'id_closing_entry',
        'coa',
        'debit',
        'credit',
    ];

    // relasi ke tabel coa
    public function toCoa()
    {
        return $this->belongsTo(Coa::class, 'coa', 'coa');
    }
}
