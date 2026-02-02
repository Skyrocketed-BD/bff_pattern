<?php

namespace App\Models\main;

use App\Models\contract_legal\Kontrak;
use App\Models\finance\Transaction;
use App\Models\finance\TransactionFull;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kontak extends Model
{
    use HasFactory;

    protected $table = 'kontak';

    protected $primaryKey = 'id_kontak';

    protected $fillable = [
        'id_kontrak',
        'id_perusahaan',
        'id_kontak_jenis',
        'name',
        'npwp',
        'phone',
        'email',
        'website',
        'address',
        'postal_code',
        'is_company'
    ];

    public function toKontrak()
    {
        return $this->setConnection('contract_legal')->belongsTo(Kontrak::class, 'id_kontrak', 'id_kontrak');
    }

    // relasi ke transaction
    public function toTransaction()
    {
        return $this->setConnection('finance')->hasMany(Transaction::class, 'id_kontak', 'id_kontak');
    }

    // relasi ke transaction_full
    public function toTransactionFull()
    {
        return $this->setConnection('finance')->hasMany(TransactionFull::class, 'id_kontak', 'id_kontak');
    }

    public function toKontakJenis()
    {
        return $this->belongsTo(KontakJenis::class, 'id_kontak_jenis', 'id_kontak_jenis');
    }

    protected static function booted()
    {
        static::creating(function ($row) {
            $row->created_by = auth('api')->user()->id_users;
        });

        static::updating(function ($row) {
            $row->updated_by = auth('api')->user()->id_users;
        });

        static::deleting(function ($kontak) {
            // cek di Transaction (DB finance)
            $hasValidTransaction = \DB::connection('finance')
                ->table('transaction')
                ->where('id_kontak', $kontak->id_kontak)
                ->where('status', 'valid')
                ->exists();

            if ($hasValidTransaction) {
                throw new \Exception("Kontak tidak bisa dihapus karena masih dipakai di Transaction dengan status valid.");
            }

            // cek di TransactionFull (DB finance)
            $hasValidTransactionFull = \DB::connection('finance')
                ->table('transaction_full')
                ->where('id_kontak', $kontak->id_kontak)
                ->where('status', 'valid')
                ->exists();

            if ($hasValidTransactionFull) {
                throw new \Exception("Kontak tidak bisa dihapus karena masih dipakai di TransactionFull dengan status valid.");
            }

            // cek di kontak / perusahaan (DB main)
            $hasValidKontak = Kontak::where('id_perusahaan', $kontak->id_kontak)->exists();

            if ($hasValidKontak) {
                throw new \Exception("Kontak tidak bisa dihapus karena masih dipakai sebagai perusahaan di kontak lain.");
            }
        });
    }
}
