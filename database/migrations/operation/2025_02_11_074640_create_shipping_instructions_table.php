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
        Schema::create('shipping_instructions', function (Blueprint $table) {
            $table->increments('id_shipping_instruction');
            $table->integer('id_plan_barging')->unsigned()->nullable()->unique();
            $table->integer('id_kontraktor')->unsigned()->nullable();
            $table->integer('id_slot')->unsigned()->nullable();
            $table->string('number_si', 50)->nullable();
            $table->string('consignee', 100)->nullable();
            $table->string('surveyor', 50)->nullable();
            $table->string('notify_party', 50)->nullable();
            $table->string('tug_boat', 50)->nullable();
            $table->string('barge', 50)->nullable();
            $table->integer('gross_tonage')->unsigned()->nullable();
            $table->text('loading_port')->nullable();
            $table->text('unloading_port')->nullable();
            $table->string('load_date_start', 50)->nullable();
            $table->string('load_date_finish', 50)->nullable();
            $table->integer('load_amount')->unsigned()->nullable();
            $table->text('information')->nullable();
            $table->binary('attachment')->nullable();
            $table->string('mining_inspector', 50)->nullable();
            $table->enum('status', ['0', '1', '2', '3', '4', '5', '6'])->default('0')->comment('0 = pending, 1 = rejected, 2 = approved by technical / waiting for execution, 3 = waiting for slotting, 4 = pending provision, 5 = provision, 6 = paid');
            $table->text('reject_reason')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_plan_barging')->references('id_plan_barging')->on('plan_bargings')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_kontraktor')->references('id_kontraktor')->on('kontraktor')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('id_slot')->references('id_slot')->on('slots')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_instructions');
    }
};
