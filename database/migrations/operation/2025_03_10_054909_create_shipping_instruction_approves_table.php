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
        Schema::create('shipping_instruction_approve', function (Blueprint $table) {
            $table->increments('id_shipping_instruction_approve');
            $table->integer('id_shipping_instruction')->unsigned()->nullable();
            $table->integer('id_users')->unsigned()->nullable();
            $table->date('date')->nullable();
            $table->enum('status', ['approved', 'rejected'])->nullable();
            $table->text('reject_reason')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_shipping_instruction')->references('id_shipping_instruction')->on('shipping_instructions')->onDelete('cascade')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_instruction_approve');
    }
};
