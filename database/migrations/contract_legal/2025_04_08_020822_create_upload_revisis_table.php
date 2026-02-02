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
        Schema::create('upload_revisi', function (Blueprint $table) {
            $table->increments('id_upload_revisi');
            $table->integer('id_revisi')->unsigned()->nullable();
            $table->integer('id_upload_kontrak_tahapan')->unsigned()->nullable();
            $table->binary('file')->nullable();

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_revisi')->references('id_revisi')->on('revisi')->onDelete('cascade');
            $table->foreign('id_upload_kontrak_tahapan')->references('id_upload_kontrak_tahapan')->on('upload_kontrak_tahapan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_revisi');
    }
};
