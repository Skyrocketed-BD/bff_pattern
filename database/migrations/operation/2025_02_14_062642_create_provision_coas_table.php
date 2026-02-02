<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'operation';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provision_coa', function (Blueprint $table) {
            $table->increments('id_provision_coa');
            $table->integer('id_provision')->unsigned()->nullable();
            $table->integer('id_journal')->unsigned()->nullable();
            
            $table->string('no_invoice', 50)->nullable();
            $table->enum('method_coa', ['coa_muat', 'coa_bongkar'])->nullable();
            $table->binary('attachment')->nullable();
            $table->binary('attachment_pnbp_final')->nullable();
            $table->date('date')->nullable();
            $table->bigInteger('price')->nullable();
            $table->bigInteger('pay_pnbp')->nullable();
            $table->decimal('hpm', 8, 2)->nullable();
            $table->decimal('hma', 8, 2)->nullable();
            $table->integer('kurs')->nullable();
            $table->decimal('ni_final', 8, 2)->nullable();
            $table->decimal('fe_final', 8, 2)->nullable();
            $table->decimal('co_final', 8, 2)->nullable();
            $table->decimal('sio2_final', 8, 2)->nullable();
            $table->decimal('mgo2_final', 8, 2)->nullable();
            $table->decimal('mc_final', 8, 2)->nullable();
            $table->decimal('tonage_final', 8, 3);
            $table->text('description')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_provision')->references('id_provision')->on('provision')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provision_coa');
    }
};
