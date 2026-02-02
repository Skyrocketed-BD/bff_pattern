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
        Schema::create('general_ledger_logs', function (Blueprint $table) {
            $table->increments('id_general_ledger_log');
            $table->string('transaction_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->integer('coa')->nullable();
            $table->enum('type', ['D', 'K'])->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->integer('revision')->nullable();
            
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
        Schema::dropIfExists('general_ledger_logs');
    }
};
