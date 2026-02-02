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
        Schema::create('closing_adjustment', function (Blueprint $table) {
            $table->increments('id_closing_adjustment');
            $table->integer('id_journal_adjustment')->unsigned()->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->string('month')->nullable();
            $table->string('year')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_journal_adjustment')->references('id_journal_adjustment')->on('journal_adjustments')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('closing_adjustment');
    }
};
