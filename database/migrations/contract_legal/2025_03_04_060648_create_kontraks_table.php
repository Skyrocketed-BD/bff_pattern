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
        Schema::create('kontrak', function (Blueprint $table) {
            $table->increments('id_kontrak');
            $table->string('no_kontrak', 50)->nullable();
            $table->string('nama_perusahaan', 128)->nullable();
            // $table->string('nama_pembeli', 50)->nullable();
            $table->date('tgl_mulai')->nullable();
            $table->date('tgl_akhir')->nullable();
            $table->enum('status', ['adendum', 'final', 'expired'])->nullable();
            $table->binary('attachment')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontrak');
    }
};
