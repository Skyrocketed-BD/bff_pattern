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
        Schema::create('asset_item', function (Blueprint $table) {
            $table->increments('id_asset_item');
            $table->integer('id_asset_head')->unsigned()->nullable();
            $table->string('asset_number', 50)->unique()->nullable();
            $table->string('identity_number', 50)->unique()->nullable();
            $table->integer('qty')->nullable();
            $table->bigInteger('price')->nullable();
            $table->bigInteger('total')->nullable();
            $table->enum('disposal', ['1', '0'])->default('0');
            $table->binary('attachment')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_asset_head')->references('id_asset_head')->on('asset_head')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_item');
    }
};
