<?php

use App\Http\Controllers\api\finance\ReportExportController;
use App\Http\Controllers\api\operation\PrintPDFController;
use App\Http\Controllers\finance\KontakUpdateController;
use App\Jobs\SendTransactionToAccounting;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect('/api/documentation');
});


// ini dipake buat testing biar cepat debugnya
Route::controller(ReportExportController::class)->prefix('excel')->group(function () {
    Route::get('/generate_pdf/{id}', 'generate_pdf')->name('generate_pdf');
    Route::get('/asset_depreciation', 'asset_depreciation')->name('asset_depreciation');
    Route::get('/asset_depreciation_excel', 'asset_depreciation_excel')->name('asset_depreciation_excel');
    Route::get('/balance', 'balance')->name('balance');
    Route::get('/journal_interface', 'journal_interface')->name('journal_interface');
    Route::get('/general_entry', 'journal_umum')->name('journal_umum');
    Route::get('/general_ledger', 'general_ledger')->name('general_ledger');
});

Route::controller(PrintPDFController::class)->prefix('report')->as('report.')->group(function () {
    Route::get('/invoice-bill', 'invoiceBill');
    Route::get('/invoice-bill-draft', 'invoiceBillDraft');
    Route::get('/invoice', 'invoice')->name('invoice');
    Route::get('/shipping_instruction', 'shipping_instruction')->name('shipping_instruction');
    Route::get('/si_number', function () {
        $zed = generateSINumber('4', 'MMS');
        return $zed;
    });
    Route::get('/rabbit', function () {
        $data = [
            'no_transaksi' => "no-0001",
            'tgl' => "2023-03-03",
        ];
        $zed = SendTransactionToAccounting::dispatch($data)->onQueue('transactions');
        // try {
        //     $connection = new AMQPStreamConnection(
        //         '127.0.0.1', 5672, 'zed', 'zed', '/'
        //     );
        //     echo "Connected to RabbitMQ!";
        //     $connection->close();
        // } catch (\Exception $e) {
        //     echo "Failed to connect: " . $e->getMessage();
        // }

    });
});


Route::controller(KontakUpdateController::class)->prefix('kontak-update')->group(function () {
    Route::get('/', 'index')->name('index');
});
