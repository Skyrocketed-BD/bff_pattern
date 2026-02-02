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
        Schema::create('tax_rate', function (Blueprint $table) {
            $table->increments('id_tax_rate');
            $table->integer('id_tax')->unsigned();
            $table->string('kd_tax', 50)->nullable()->unique();
            $table->text('name')->nullable();
            $table->float('rate')->nullable();
            $table->text('ref')->nullable();
            $table->enum('count', ['y', 'n'])->default('y')->comment('y = count, n = not count | untuk kebutuhan rate 0 dibebaskan hitung atau tidak');
            $table->date('effective_date')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_tax')->references('id_tax')->on('tax')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rate');
    }
};
