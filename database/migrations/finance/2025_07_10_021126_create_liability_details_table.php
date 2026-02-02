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
        Schema::create('liability_details', function (Blueprint $table) {
            $table->increments('id_liability_detail');
            $table->unsignedInteger('id_liability')->nullable();
            $table->unsignedInteger('id_invoice_bill')->nullable();

            $table->enum('category', ['penerimaan', 'pengeluaran'])->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->bigInteger('value')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['valid', 'reversed', 'deleted'])->default('valid');
            $table->binary('attachment')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_liability')->references('id_liability')->on('liability')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_invoice_bill')->references('id_invoice_bill')->on('invoice_bills')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liability_details');
    }
};
