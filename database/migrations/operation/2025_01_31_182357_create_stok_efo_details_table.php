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
        Schema::create('stok_efo_detail', function (Blueprint $table) {
            $table->increments('id_stok_efo_detail');
            $table->integer('id_stok_efo')->unsigned()->nullable();
            $table->integer('id_stok_eto')->unsigned()->nullable();
            $table->integer('id_dom_eto')->unsigned()->nullable();
            $table->integer('id_stok_in_pit')->unsigned()->nullable();
            $table->integer('id_dom_in_pit')->unsigned()->nullable();

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

            $table->foreign('id_stok_efo')->references('id_stok_efo')->on('stok_efos')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_stok_eto')->references('id_stok_eto')->on('stok_etos')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_dom_eto')->references('id_dom_eto')->on('dom_eto')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_efo_detail');
    }
};
