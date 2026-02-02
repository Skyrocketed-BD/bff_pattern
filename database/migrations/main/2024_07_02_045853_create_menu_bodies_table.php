<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'mysql';
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_body', function (Blueprint $table) {
            $table->increments('id_menu_body');
            $table->integer('id_menu_category')->unsigned()->nullable();
            $table->string('name', 50)->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('url', 50)->nullable();
            $table->integer('position')->nullable();
            $table->enum('is_enabled', ['0', '1'])->default('1');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_menu_category')->references('id_menu_category')->on('menu_category')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_body');
    }
};
