<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'operation';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_fob', function (Blueprint $table) {
            $table->increments('id_invoice_fob');
            $table->integer('id_plan_barging')->unsigned()->nullable()->unique();
            $table->integer('id_journal')->unsigned()->nullable();
            $table->integer('id_kontak')->unsigned()->nullable();

            $table->string('transaction_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->integer('kurs')->nullable();
            $table->decimal('hpm', 8, 2)->nullable();
            $table->decimal('hma', 8, 2)->nullable();
            $table->decimal('ni', 8, 2)->nullable();
            $table->decimal('mc', 8, 2)->nullable();
            $table->decimal('tonage', 8, 2)->nullable();
            $table->bigInteger('price')->nullable();
            $table->text('description')->nullable();
            $table->string('reference_number', 50)->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_plan_barging')->references('id_plan_barging')->on('plan_bargings')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_fob');
    }
};
