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
        Schema::create('coa_body', function (Blueprint $table) {
            $table->increments('id_coa_body');
            $table->integer('id_coa_head')->unsigned()->nullable();
            $table->integer('id_coa_clasification')->unsigned()->nullable();
            $table->string('name', 50)->nullable();
            $table->integer('coa')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_coa_head')->references('id_coa_head')->on('coa_head')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_coa_clasification')->references('id_coa_clasification')->on('coa_clasification')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coa_body');
    }
};
