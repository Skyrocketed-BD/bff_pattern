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
        Schema::create('drilling', function (Blueprint $table) {
            $table->increments('id_drilling');
            $table->integer('id_kontraktor')->unsigned()->nullable();
            $table->string('name', 50)->nullable();
            $table->binary('file')->nullable();

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
        Schema::dropIfExists('drilling');
    }
};
