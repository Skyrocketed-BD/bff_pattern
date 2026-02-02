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
        Schema::create('infrastructures', function (Blueprint $table) {
            $table->increments('id_infrastructure');
            $table->integer('id_kontraktor')->unsigned()->nullable();
            $table->string('name', 50)->nullable();
            $table->binary('file')->nullable();
            $table->enum('category', ['sedimen_pond', 'nursery', 'lab', 'mess', 'office', 'workshop', 'fuel_storage', 'stockpile_eto_efo', 'dome', 'water_channel', 'hauling'])->nullable();

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
        Schema::dropIfExists('infrastructures');
    }
};
