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
        Schema::create('journal_adjustments', function (Blueprint $table) {
            $table->increments('id_journal_adjustment');
            $table->string('transaction_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->bigInteger('value')->nullable();
            $table->text('description')->nullable();
            $table->enum('transaction_type', ['one_time', 'recurring'])->default('one_time');
            $table->integer('duration')->default(0);
            $table->integer('remaining')->default(0);
            $table->string('reference_number', 50)->nullable();
            $table->enum('status', ['valid', 'reversed', 'deleted'])->default('valid');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_adjustments');
    }
};
