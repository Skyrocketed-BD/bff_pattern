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
        Schema::create('report_title', function (Blueprint $table) {
            $table->increments('id_report_title');
            $table->integer('id_report_menu')->unsigned()->nullable();
            $table->string('name', 50)->nullable();
            $table->enum('type', ['default', 'formula', 'input'])->nullable()->default('default');
            $table->integer('value')->nullable();
            $table->enum('display_currency', ['on', 'off'])->default('on');
            $table->integer('order')->nullable();
            $table->enum('is_formula', ['0', '1'])->nullable()->default('0');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_report_menu')->references('id_report_menu')->on('report_menu')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_title');
    }
};
