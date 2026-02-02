<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // specific connection database
    protected $connection = 'mysql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kontak', function (Blueprint $table) {
            $table->increments('id_kontak');
            $table->integer('id_kontrak')->unsigned()->nullable();
            $table->integer('id_perusahaan')->unsigned()->nullable();
            $table->integer('id_kontak_jenis')->unsigned()->nullable();
            $table->string('name', 50)->nullable();
            $table->string('npwp', 25)->nullable();
            $table->string('phone', 16)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->integer('postal_code')->nullable();
            $table->boolean('is_company')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_kontak_jenis')->references('id_kontak_jenis')->on('kontak_jenis')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontak');
    }
};
