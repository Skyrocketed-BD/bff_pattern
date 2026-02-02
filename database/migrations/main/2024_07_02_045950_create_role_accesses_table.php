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
        Schema::create('role_access', function (Blueprint $table) {
            $table->increments('id_role_access');
            $table->integer('id_menu_module')->unsigned()->nullable();
            $table->integer('id_menu_body')->unsigned()->nullable();
            $table->integer('id_role')->unsigned()->nullable();
            $table->enum('action', ['crud', 'view'])->default('crud');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_menu_module')->references('id_menu_module')->on('menu_module')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_menu_body')->references('id_menu_body')->on('menu_body')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_role')->references('id_role')->on('role')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_access');
    }
};
