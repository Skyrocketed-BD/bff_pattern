<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_disposal', function (Blueprint $table) {
            $table->increments('id_asset_disposal');
            $table->integer('id_transaction')->unsigned()->nullable();
            $table->integer('id_transaction_full')->unsigned()->nullable();
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->binary('attachment')->nullable();
            $table->enum('status', ['hilang', 'rusak', 'jual'])->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_transaction')->references('id_transaction')->on('transaction')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_transaction_full')->references('id_transaction_full')->on('transaction_full')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposal');
    }
};
