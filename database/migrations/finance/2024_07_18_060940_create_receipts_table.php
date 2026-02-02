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
        Schema::create('receipts', function (Blueprint $table) {
            $table->increments('id_receipt');
            $table->unsignedInteger('id_kontak')->nullable();
            $table->unsignedInteger('id_journal')->nullable();
            
            $table->string('transaction_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('receive_from', 50)->nullable();
            $table->enum('pay_type', ['c', 'cc', 'bt'])->description('c = cash, cc = credit card, bt = bank transfer')->nullable();
            $table->enum('record_type', ['bank', 'cash', 'petty_cash'])->nullable();
            $table->bigInteger('value')->nullable();
            $table->text('description')->nullable();
            $table->binary('attachment')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->enum('in_ex', ['y', 'n', 'o'])->default('o')->description('y = exclude tax, n = include tax, o = none');
            $table->enum('status', ['valid', 'reversed', 'deleted'])->default('valid');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_journal')->references('id_journal')->on('journal')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
