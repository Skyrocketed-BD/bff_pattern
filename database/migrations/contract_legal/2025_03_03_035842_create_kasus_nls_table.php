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
        Schema::create('kasus_nl', function (Blueprint $table) {
            $table->increments('id_kasus_nl');
            $table->integer('id_tahapan_nl')->unsigned()->nullable();
            $table->string('no', 50)->nullable();
            $table->string('nama', 128)->nullable();
            $table->date('tanggal')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status', ['lanjut', 'transfer', 'cabut'])->default('lanjut');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_tahapan_nl')->references('id_tahapan_nl')->on('tahapan_nl')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kasus_nl');
    }
};
