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
        Schema::create('invoice_bill_details', function (Blueprint $table) {
            $table->increments('id_invoice_bill_detail');
            $table->unsignedInteger('id_invoice_bill')->nullable();
            $table->integer('coa')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->enum('type', ['D', 'K'])->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_invoice_bill')->references('id_invoice_bill')->on('invoice_bills')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_bill_details');
    }
};
