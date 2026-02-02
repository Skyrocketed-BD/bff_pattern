<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'finance';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ini adalah tabel menampung invoice masuk dan keluar
        Schema::create('invoice_bills', function (Blueprint $table) {
            $table->increments('id_invoice_bill');
            $table->unsignedInteger('id_kontak')->nullable();
            $table->unsignedInteger('id_journal')->nullable();

            $table->string('transaction_number', 50)->unique()->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->date('inv_date')->nullable();
            $table->date('due_date')->nullable();
            $table->bigInteger('total')->nullable();
            $table->text('description')->nullable();
            $table->enum('category', ['penerimaan', 'pengeluaran'])->nullable();
            $table->enum('type', ['transaction', 'transaction_full', 'down_payment', 'advance_payment'])->nullable();
            $table->enum('in_ex', ['y', 'n', 'o'])->default('o')->description('y = exclude tax, n = include tax, o = none');
            $table->enum('payment_status', ['issued', 'partial', 'paid'])->default('issued');
            $table->boolean('is_outstanding')->default(false);
            $table->boolean('is_actual')->default(false); // jika status true sudah diakui secara akunting / finance

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_journal')->references('id_journal')->on('journal')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_bills');
    }
};
