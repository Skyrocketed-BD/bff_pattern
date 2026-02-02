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
        // ini adalah tabel invoice_installments untuk menagih invoice yang sudah diakui
        Schema::create('transaction_term', function (Blueprint $table) {
            $table->increments('id_transaction_term');
            $table->unsignedInteger('id_transaction')->nullable();
            $table->unsignedInteger('id_receipt')->nullable();

            $table->string('invoice_number', 50)->nullable();
            $table->string('nama', 50)->nullable();
            $table->date('date')->nullable();
            $table->decimal('percent', 3, 2)->nullable();
            $table->bigInteger('value_ppn')->nullable();
            $table->bigInteger('value_pph')->nullable();
            $table->bigInteger('value_percent')->nullable();
            $table->bigInteger('value_deposit')->nullable();
            $table->enum('deposit', ['down_payment', 'advance_payment'])->nullable();
            $table->boolean('final')->default(false);

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_transaction')->references('id_transaction')->on('transaction')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_receipt')->references('id_receipt')->on('receipts')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_term');
    }
};
