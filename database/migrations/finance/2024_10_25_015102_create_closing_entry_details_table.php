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
        Schema::create('closing_entry_details', function (Blueprint $table) {
            $table->increments('id_closing_entry_detail');
            $table->integer('id_closing_entry')->unsigned();
            $table->integer('coa')->nullable();
            $table->bigInteger('debit')->nullable();
            $table->bigInteger('credit')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_closing_entry')->references('id_closing_entry')->on('closing_entries')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('closing_entry_details');
    }
};
