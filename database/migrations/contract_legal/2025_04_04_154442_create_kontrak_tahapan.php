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
        Schema::create('kontrak_tahapan', function (Blueprint $table) {
            $table->increments('id_kontrak_tahapan');
            $table->integer('id_tahapan_k')->unsigned();
            $table->integer('id_kontrak')->unsigned();
            $table->date('tgl');
            $table->text('keterangan')->nullable();
            $table->enum('status', ['y', 'n', 'a'])->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_tahapan_k')->references('id_tahapan_k')->on('tahapan_k')->onDelete('cascade');
            $table->foreign('id_kontrak')->references('id_kontrak')->on('kontrak')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontrak_tahapan');
    }
};
