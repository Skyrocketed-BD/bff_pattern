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
        Schema::create('transaction_tax', function (Blueprint $table) {
            $table->increments('id_transaction_tax');
            $table->string('transaction_number', 50)->nullable();
            $table->integer('id_coa')->unsigned()->nullable();
            $table->integer('id_tax')->unsigned()->nullable();
            $table->integer('id_tax_rate')->unsigned()->nullable();
            $table->float('rate')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_coa')->references('id_coa')->on('coa')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_tax')->references('id_tax')->on('tax')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_tax_rate')->references('id_tax_rate')->on('tax_rate')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_tax');
    }
};
