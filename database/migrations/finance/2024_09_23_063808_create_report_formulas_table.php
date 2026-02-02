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
        Schema::create('report_formula', function (Blueprint $table) {
            $table->increments('id_report_formula');
            $table->integer('id_report_title')->unsigned()->nullable();
            $table->integer('id_report_title_select')->unsigned()->nullable();
            $table->enum('operation', ['+', '-', '*', '/'])->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_report_title')->references('id_report_title')->on('report_title')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_formula');
    }
};
