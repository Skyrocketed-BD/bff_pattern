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
        Schema::create('journal_closing_entry_sets', function (Blueprint $table) {
            $table->increments('id_journal_closing_entry_set');
            $table->integer('id_journal_closing_entry')->unsigned()->nullable();
            $table->integer('id_coa')->unsigned()->nullable();
            $table->enum('type', ['D', 'K'])->nullable();
            $table->bigInteger('value')->nullable();
            $table->integer('serial_number')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_journal_closing_entry')->references('id_journal_closing_entry')->on('journal_closing_entries')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_coa')->references('id_coa')->on('coa')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_closing_entry_sets');
    }
};
