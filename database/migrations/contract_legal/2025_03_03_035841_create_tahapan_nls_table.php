<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'contract_legal';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tahapan_nl', function (Blueprint $table) {
            $table->increments('id_tahapan_nl');
            $table->string('name', 50)->nullable();

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
        Schema::dropIfExists('tahapan_nl');
    }
};
