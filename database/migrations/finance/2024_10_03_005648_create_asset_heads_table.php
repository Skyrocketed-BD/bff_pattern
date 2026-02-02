<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'finance';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_head', function (Blueprint $table) {
            $table->increments('id_asset_head');
            $table->integer('id_asset_coa')->unsigned()->nullable();
            $table->integer('id_asset_group')->unsigned()->nullable();
            $table->integer('id_asset_category')->unsigned()->nullable();
            $table->integer('id_transaction')->unsigned()->nullable();
            $table->integer('id_transaction_full')->unsigned()->nullable();
            $table->string('name', 50)->nullable();
            $table->date('tgl')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_asset_coa')->references('id_asset_coa')->on('asset_coa')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_asset_group')->references('id_asset_group')->on('asset_group')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_transaction')->references('id_transaction')->on('transaction')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_transaction_full')->references('id_transaction_full')->on('transaction_full')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_head');
    }
};
