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
        Schema::create('stok_in_pits', function (Blueprint $table) {
            $table->increments('id_stok_in_pit');
            $table->integer('id_kontraktor')->unsigned()->nullable();
            $table->integer('id_block')->unsigned()->nullable();
            $table->integer('id_pit')->unsigned()->nullable();
            $table->integer('id_dom_in_pit')->unsigned()->nullable();
            $table->string('sample_id', 50)->unique()->nullable();
            $table->date('date')->nullable();
            $table->decimal('ni', 8, 2)->nullable();
            $table->decimal('fe', 8, 2)->nullable();
            $table->decimal('co', 8, 2)->nullable();
            $table->decimal('sio2', 8, 2)->nullable();
            $table->decimal('mgo2', 8, 2)->nullable();
            $table->decimal('tonage', 8, 2)->nullable();
            $table->integer('ritasi')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->foreign('id_kontraktor')->references('id_kontraktor')->on('kontraktor')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_block')->references('id_block')->on('block')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_pit')->references('id_pit')->on('pit')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_dom_in_pit')->references('id_dom_in_pit')->on('dom_in_pit')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_in_pits');
    }
};
