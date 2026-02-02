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
        Schema::create('asset_coa', function (Blueprint $table) {
            $table->increments('id_asset_coa');
            $table->integer('id_coa')->unsigned()->nullable();
            $table->integer('id_coa_acumulated')->unsigned()->nullable();
            $table->integer('id_coa_expense')->unsigned()->nullable();
            $table->string('name', 50)->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_coa')->references('id_coa')->on('coa')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_coa');
    }
};
