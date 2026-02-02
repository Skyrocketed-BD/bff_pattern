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
        Schema::create('kontraktor', function (Blueprint $table) {
            $table->increments('id_kontraktor');
            $table->string('company', 50)->nullable();
            $table->string('leader', 50)->nullable();
            $table->string('npwp', 16)->nullable();
            $table->string('telepon', 16)->nullable();
            $table->string('address')->nullable();
            $table->integer('postal_code')->length(5)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('initial', 10)->nullable();
            $table->string('color', 10)->nullable();
            $table->enum('capital', ['nasional', 'asing'])->default('nasional');

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
        Schema::dropIfExists('kontraktor');
    }
};
