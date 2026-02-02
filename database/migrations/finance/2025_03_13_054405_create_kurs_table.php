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
        Schema::create('kurs', function (Blueprint $table) {
            $table->increments('id_kurs');
            $table->date('date')->nullable();
            $table->float('jual', 8, 2)->nullable();
            $table->float('beli', 8, 2)->nullable();
            $table->float('tengah', 8, 2)->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kurs');
    }
};
