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
        Schema::create('transaction_full', function (Blueprint $table) {
            $table->increments('id_transaction_full');
            $table->unsignedInteger('id_kontak')->nullable();
            $table->unsignedInteger('id_journal')->nullable();
            $table->unsignedInteger('id_invoice_bill')->nullable();
            
            $table->string('transaction_number', 50)->nullable();
            $table->string('invoice_number', 50)->nullable();
            $table->string('efaktur_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('from_or_to', 50)->nullable();
            $table->bigInteger('value')->nullable();
            $table->text('description')->nullable();
            $table->binary('attachment')->nullable();
            $table->enum('category', ['penerimaan', 'pengeluaran'])->nullable();
            $table->enum('record_type', ['bank', 'cash', 'petty_cash'])->nullable();
            $table->enum('in_ex', ['y', 'n', 'o'])->default('o')->description('y = exclude tax, n = include tax, o = none');
            $table->enum('status', ['valid', 'reversed', 'deleted'])->default('valid');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_journal')->references('id_journal')->on('journal')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_invoice_bill')->references('id_invoice_bill')->on('invoice_bills')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_full');
    }
};
