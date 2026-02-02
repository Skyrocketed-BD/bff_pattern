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
        Schema::create('report_body', function (Blueprint $table) {
            $table->increments('id_report_body');
            $table->integer('id_report_title')->unsigned()->nullable();
            $table->integer('id_report_menu')->unsigned()->nullable();
            $table->integer('id_coa_body')->unsigned()->nullable();
            $table->integer('id_coa')->unsigned()->nullable();
            $table->enum('method', ['coa', 'subcoa', 'range', 'report'])->nullable()->default('coa');
            $table->enum('operation', ['+', '-', '*', '/'])->nullable();
            
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_report_title')->references('id_report_title')->on('report_title')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_report_menu')->references('id_report_menu')->on('report_menu')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_coa_body')->references('id_coa_body')->on('coa_body')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_coa')->references('id_coa')->on('coa')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_body');
    }
};
