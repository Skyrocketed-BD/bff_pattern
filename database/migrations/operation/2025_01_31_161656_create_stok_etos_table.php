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
        Schema::create('stok_etos', function (Blueprint $table) {
            $table->increments('id_stok_eto');
            $table->integer('id_kontraktor')->unsigned()->nullable();
            $table->integer('id_dom_eto')->unsigned()->nullable();
            
            $table->date('date_in')->nullable();
            $table->date('date_out')->nullable();
            $table->decimal('tonage_after', 8, 2)->nullable();
            $table->enum('mining_recovery_type', ['truck_factor', 'survey', 'scale'])->nullable();
            $table->decimal('mining_recovery_value', 8, 2)->nullable();
            $table->binary('attachment')->nullable();
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
            $table->foreign('id_dom_eto')->references('id_dom_eto')->on('dom_eto')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_etos');
    }
};
