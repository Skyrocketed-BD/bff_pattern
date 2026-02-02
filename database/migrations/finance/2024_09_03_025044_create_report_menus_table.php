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
        Schema::create('report_menu', function (Blueprint $table) {
            $table->index('id_report_menu');
            $table->increments('id_report_menu');
            $table->string('name', 50)->nullable();
            $table->enum('is_annual', ['0', '1'])->nullable()->comment('0 = monthly, 1 = annual')->default('0');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_menu');
    }
};
