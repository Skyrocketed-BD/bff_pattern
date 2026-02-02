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
        Schema::create('plan_bargings', function (Blueprint $table) {
            $table->increments('id_plan_barging');
            $table->integer('id_kontraktor')->unsigned()->nullable();
            $table->string('pb_name', 50)->nullable();
            $table->date('date')->nullable();
            $table->binary('attachment')->nullable();
            $table->enum('shipping_method', ['cif', 'fob'])->nullable();

            $table->decimal('ni', 8, 2)->nullable();
            $table->decimal('fe', 8, 2)->nullable();
            $table->decimal('co', 8, 2)->nullable();
            $table->decimal('sio2', 8, 2)->nullable();
            $table->decimal('mgo2', 8, 2)->nullable();
            $table->decimal('tonage', 8, 2)->nullable();
            $table->integer('mc')->nullable();
            $table->integer('ritasi')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_kontraktor')->references('id_kontraktor')->on('kontraktor')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_bargings');
    }
};
