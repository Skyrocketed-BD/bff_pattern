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
        Schema::create('bank_administration', function (Blueprint $table) {
            $table->increments('id_bank_administration');
            $table->integer('id_coa_bank')->unsigned()->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('bank_fee')->nullable();
            $table->enum('status', ['valid', 'reversed', 'deleted'])->default('valid');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_coa_bank')->references('id_coa')->on('coa')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_administration');
    }
};
