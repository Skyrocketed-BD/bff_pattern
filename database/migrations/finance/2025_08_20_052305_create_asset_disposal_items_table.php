<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_disposal_item', function (Blueprint $table) {
            $table->increments('id_asset_disposal_item');
            $table->integer('id_asset_disposal')->unsigned()->nullable();
            $table->integer('id_asset_item')->unsigned()->nullable();
            $table->bigInteger('purchase_price')->nullable();
            $table->bigInteger('book_value')->nullable();
            $table->bigInteger('selling_price')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_asset_disposal')->references('id_asset_disposal')->on('asset_disposal')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_asset_item')->references('id_asset_item')->on('asset_item')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_disposal_item');
    }
};
