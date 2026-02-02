<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'contract_legal';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwal_sidang', function (Blueprint $table) {
            $table->increments('id_jadwal_sidang');
            $table->integer('id_kasus_riwayat_l')->unsigned()->nullable();
            $table->string('no', 50)->nullable();
            $table->string('nama', 128)->nullable();
            $table->timestamp('tgl_waktu_sidang')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status', ['lanjut', 'cabut'])->default('lanjut');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_kasus_riwayat_l')->references('id_kasus_riwayat_l')->on('kasus_riwayat_l')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_sidang');
    }
};
