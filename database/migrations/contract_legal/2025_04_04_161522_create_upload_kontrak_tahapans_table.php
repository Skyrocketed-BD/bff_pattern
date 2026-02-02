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
        Schema::create('upload_kontrak_tahapan', function (Blueprint $table) {
            $table->increments('id_upload_kontrak_tahapan');
            $table->integer('id_kontrak_tahapan')->unsigned();
            $table->string('judul', 128)->nullable();
            $table->binary('file')->nullable();
            
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_kontrak_tahapan')->references('id_kontrak_tahapan')->on('kontrak_tahapan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_kontrak_tahapan');
    }
};
