<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'operation';
    
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provision', function (Blueprint $table) {
            $table->increments('id_provision');
            $table->integer('id_shipping_instruction')->unsigned()->nullable();
            
            $table->string('inv_provision', 50)->nullable();
            $table->enum('method_sales', ['cif', 'fob'])->nullable();
            $table->date('departure_date')->nullable();
            $table->bigInteger('pnbp_provision')->nullable();
            $table->bigInteger('selling_price')->nullable();
            $table->decimal('tonage_actual', 8, 2)->nullable();
            $table->binary('attachment')->nullable();
            $table->binary('attachment_pnbp_prov')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_shipping_instruction')->references('id_shipping_instruction')->on('shipping_instructions')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provision');
    }
};
