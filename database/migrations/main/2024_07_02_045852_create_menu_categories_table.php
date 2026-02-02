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
        Schema::create('menu_category', function (Blueprint $table) {
            $table->increments('id_menu_category');
            $table->integer('id_menu_module')->unsigned()->nullable();
            $table->string('name', 50)->nullable();
            $table->integer('position')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_menu_module')->references('id_menu_module')->on('menu_module')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_category');
    }
};
