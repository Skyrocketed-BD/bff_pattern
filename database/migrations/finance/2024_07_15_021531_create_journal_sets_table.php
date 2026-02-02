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
        Schema::create('journal_set', function (Blueprint $table) {
            $table->increments('id_journal_set');
            $table->integer('id_tax_rate')->unsigned()->nullable();
            $table->integer('id_journal')->unsigned()->nullable();
            $table->integer('id_coa')->unsigned()->nullable();
            $table->enum('type', ['D', 'K'])->nullable();
            $table->enum('open_input', ['y', 'n'])->default('n')->description('y = open, n = close');
            $table->integer('serial_number')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_tax_rate')->references('id_tax_rate')->on('tax_rate')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_journal')->references('id_journal')->on('journal')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_coa')->references('id_coa')->on('coa')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_set');
    }
};
