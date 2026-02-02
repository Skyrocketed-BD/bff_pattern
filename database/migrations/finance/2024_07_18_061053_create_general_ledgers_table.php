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
        Schema::create('general_ledgers', function (Blueprint $table) {
            $table->increments('id_general_ledger');
            $table->unsignedInteger('id_kontak')->nullable();
            $table->unsignedInteger('id_journal')->nullable();

            $table->string('transaction_number', 50)->nullable();
            $table->date('date')->nullable();
            $table->integer('coa')->nullable();
            $table->enum('type', ['D', 'K'])->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->enum('jb', ['0', '1'])->default('0')->description('1 = yes, 0 = no');
            $table->enum('closed', ['0', '1'])->default('0')->description('1 = yes, 0 = no');
            $table->enum('phase', ['opr', 'cls', 'int', 'acm', 'tax'])->nullable()->description('opr = operasional, cls = closing, int = initial balance, acm = accumulation');
            $table->enum('calculated', ['0', '1'])->default('1')->nullable()->description('0 = count by system (discount, dp, tax, asset), 1 = count after calculation by system');

            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->foreign('id_journal')->references('id_journal')->on('journal')->onDelete('set null')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_ledgers');
    }
};
