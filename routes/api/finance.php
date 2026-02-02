<?php

use App\Http\Controllers\api\finance\AssetCategoryController;
use App\Http\Controllers\api\finance\AssetCoaController;
use App\Http\Controllers\api\finance\AssetDisposalController;
use App\Http\Controllers\api\finance\AssetGroupController;
use App\Http\Controllers\api\finance\AssetItemController;
use App\Http\Controllers\api\finance\AssetPurchaseController;
use App\Http\Controllers\api\finance\BankAdministrationController;
use App\Http\Controllers\api\finance\BankNCashController;
use App\Http\Controllers\api\finance\BankReconciliationController;
use App\Http\Controllers\api\finance\ClosingAdjustmentController;
use App\Http\Controllers\api\finance\ClosingDepreciationController;
use App\Http\Controllers\api\finance\ClosingEntryController;
use App\Http\Controllers\api\finance\CoaBodyController;
use App\Http\Controllers\api\finance\CoaClasificationController;
use App\Http\Controllers\api\finance\CoaController;
use App\Http\Controllers\api\finance\CoaGroupController;
use App\Http\Controllers\api\finance\CoaHeadController;
use App\Http\Controllers\api\finance\DashboardController;
use App\Http\Controllers\api\finance\DownPaymentsController;
use App\Http\Controllers\api\finance\ExpenditureController;
use App\Http\Controllers\api\finance\GeneralLedgerController;
use App\Http\Controllers\api\finance\InitialBalanceController;
use App\Http\Controllers\api\finance\InvoiceBillController;
use App\Http\Controllers\api\finance\JournalAdjustmentController;
use App\Http\Controllers\api\finance\JournalClosingEntryController;
use App\Http\Controllers\api\finance\JournalController;
use App\Http\Controllers\api\finance\JournalEntryController;
use App\Http\Controllers\api\finance\JournalSetController;
use App\Http\Controllers\api\finance\KartuUtangPiutangController;
use App\Http\Controllers\api\finance\KursController;
use App\Http\Controllers\api\finance\LiabilityController;
use App\Http\Controllers\api\finance\PrintInvoiceController;
use App\Http\Controllers\api\finance\ReceiptsController;
use App\Http\Controllers\api\finance\ReportBodyController;
use App\Http\Controllers\api\finance\ReportMenuController;
use App\Http\Controllers\api\finance\ReportTitleController;
use App\Http\Controllers\api\finance\SearchController;
use App\Http\Controllers\api\finance\SwitchingController;
use App\Http\Controllers\api\finance\TaxCoaController;
use App\Http\Controllers\api\finance\TaxController;
use App\Http\Controllers\api\finance\TaxRateController;
use App\Http\Controllers\api\finance\TransactionController;
use App\Http\Controllers\api\finance\TransactionFullController;
use App\Http\Controllers\api\finance\TransactionTaxController;
use App\Http\Controllers\api\finance\ReportExportController;
use App\Http\Controllers\api\finance\TaxLiabilityController;
use App\Http\Controllers\api\finance\TransactionTermController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'checkClosingYear',
], function () {
    // begin:: transaction
    Route::prefix('transactions')->group(function () {
        Route::get('/details', [TransactionController::class, 'details']);
        Route::get('/{type?}', [TransactionController::class, 'index']);
        Route::post('/{payment?}', [TransactionController::class, 'store'])->middleware([
            'CheckPreferenceKey:receive_coa_discount,expense_coa_discount',
            'CheckCoaPreference:receive_coa_discount,expense_coa_discount'
        ]);
        Route::get('/filter/{category}', [TransactionController::class, 'filter']);
        Route::delete('/', [TransactionController::class, 'destroy']);
    });
    // end:: transaction

    // begin:: receipt
    Route::prefix('receipts')->group(function () {
        Route::get('/{type?}', [ReceiptsController::class, 'index']);
        Route::post('/', [ReceiptsController::class, 'store'])->middleware([
            'CheckPreferenceKey:down_payment_adjustment_journal,advance_payment_adjustment_coa',
            'CheckCoaPreference:advance_payment_adjustment_coa',
            'CheckJournalPreference:down_payment_adjustment_journal'
        ]);
        Route::delete('/', [ReceiptsController::class, 'destroy']);
    });
    // end:: receipt

    // begin:: expenditures
    Route::prefix('expenditures')->group(function () {
        Route::get('/{type?}', [ExpenditureController::class, 'index']);
        Route::post('/', [ExpenditureController::class, 'store'])->middleware([
            'CheckPreferenceKey:down_payment_adjustment_journal,advance_payment_adjustment_coa',
            'CheckCoaPreference:advance_payment_adjustment_coa',
            'CheckJournalPreference:down_payment_adjustment_journal'
        ]);
        Route::delete('/', [ExpenditureController::class, 'destroy']);
    });
    // end:: expenditures

    // begin:: journal entries
    Route::prefix('journal-entries')->group(function () {
        Route::get('/', [JournalEntryController::class, 'index']);
        Route::post('/', [JournalEntryController::class, 'store']);
        Route::delete('/{no_transaction}', [JournalEntryController::class, 'destroy']);
    });
    // end:: journal entries

    // begin:: journal adjustment
    Route::prefix('journal-adjustment')->group(function () {
        Route::get('/search-by-set', [JournalAdjustmentController::class, 'searchByIdSet']);
        Route::get('/', [JournalAdjustmentController::class, 'index']);
        Route::post('/', [JournalAdjustmentController::class, 'store']);
        Route::delete('/{no_transaction}', [JournalAdjustmentController::class, 'destroy']);
    });
    // end:: journal adjustment

    // begin:: journal closing entry
    Route::prefix('journal-closing-entry')->group(function () {
        Route::get('/', [JournalClosingEntryController::class, 'index']);
        Route::post('/', [JournalClosingEntryController::class, 'store']);
        Route::delete('/{no_transaction}', [JournalClosingEntryController::class, 'destroy']);
    });
    // end:: journal closing entry

    // begin:: bank administration
    Route::prefix('bank-administration')->group(function () {
        Route::get('/', [BankAdministrationController::class, 'index']);
        Route::post('/', [BankAdministrationController::class, 'store'])->middleware([
            'CheckPreferenceKey:bank_fee_coa',
            'CheckCoaPreference:bank_fee_coa'
        ]);
        Route::delete('/{no_transaction}', [BankAdministrationController::class, 'destroy']);
    });
    // end:: bank administration

    // begin:: bank reconciliation
    Route::prefix('bank-reconciliation')->group(function () {
        Route::get('/', [BankReconciliationController::class, 'index']);
        Route::post('/', [BankReconciliationController::class, 'store'])->middleware([
            'CheckPreferenceKey:bank_interest_coa,bank_interest_tax_coa',
            'CheckCoaPreference:bank_interest_coa,bank_interest_tax_coa'
        ]);
        Route::get('/details', [BankReconciliationController::class, 'details']);
        Route::delete('/{no_transaction}', [BankReconciliationController::class, 'destroy']);
    });
    // end:: bank reconciliation

    // begin:: tax liability
    Route::prefix('tax-liability')->group(function () {
        Route::get('/', [TaxLiabilityController::class, 'index']);
        Route::post('/', [TaxLiabilityController::class, 'store']);
        Route::delete('/{no_transaction}', [TaxLiabilityController::class, 'destroy']);
    });
    // end:: tax liability

    // begin:: initial balance
    Route::prefix('initial-balances')->group(function () {
        Route::get('/', [InitialBalanceController::class, 'index']);
        Route::post('/', [InitialBalanceController::class, 'store']);
        Route::delete('/{no_transaction}', [InitialBalanceController::class, 'destroy']);
    });
    // end:: initial balance

    // begin:: switching
    Route::prefix('switching')->group(function () {
        Route::get('/', [SwitchingController::class, 'index']);
        Route::post('/', [SwitchingController::class, 'store']);
        Route::delete('/{no_transaction}', [SwitchingController::class, 'destroy']);
    });
    // end:: switching

    // begin:: transaction full
    Route::prefix('transaction-fulls')->group(function () {
        Route::get('/details', [TransactionFullController::class, 'details']);
        Route::get('/adjustment/{category?}', [TransactionFullController::class, 'adjustment']);
        Route::get('/{category?}/{type?}', [TransactionFullController::class, 'index']);
        Route::post('/', [TransactionFullController::class, 'store']);
        Route::delete('/', [TransactionFullController::class, 'destroy']);
    });
    // end:: transaction full
});

Route::group([
    'middleware' => 'checkClosingYear:period',
], function () {
    // begin:: closing depreciations
    Route::prefix('closing-depreciations')->group(function () {
        Route::get('/', [ClosingDepreciationController::class, 'index']);
        Route::post('/', [ClosingDepreciationController::class, 'store'])->middleware([
            'CheckPreferenceKey:lifespan,cutoff_date',
        ]);
        Route::post('/open', [ClosingDepreciationController::class, 'open']);
    });
    // end:: closing depreciations

    // begin:: closing adjustments
    Route::prefix('closing-adjustment')->group(function () {
        Route::get('/', [ClosingAdjustmentController::class, 'index']);
        Route::post('/', [ClosingAdjustmentController::class, 'store']);
        Route::post('/open', [ClosingAdjustmentController::class, 'open']);
    });
    // end:: closing adjustments

    // begin:: transaction tax
    Route::prefix('transaction-tax')->group(function () {
        Route::get('/{type}/{id?}', [TransactionTaxController::class, 'index']);
        Route::put('/{type}', [TransactionTaxController::class, 'update']);
    });
    // end:: transaction tax

    // begin:: journal
    Route::prefix('journals')->group(function () {
        Route::get('/', [JournalController::class, 'index']);
        Route::post('/', [JournalController::class, 'store']);
        Route::put('/{id}', [JournalController::class, 'update']);
        Route::delete('/{id}', [JournalController::class, 'destroy']);
        Route::get('/filter/{type}/{alocation?}/{is_outstanding?}', [JournalController::class, 'filter']);
        Route::get('filter-outstanding/{type}/{is_outstanding}', [JournalController::class, 'filterWOutstanding']);
    });
    // end:: journal

    // begin:: journal settings
    Route::prefix('journal-sets')->group(function () {
        Route::get('/{id_journal}', [JournalSetController::class, 'index']);
        Route::get('/show/{id_journal_set}', [JournalSetController::class, 'show']);
        Route::put('/update/{id_journal_set}', [JournalSetController::class, 'update']);
        Route::delete('destroy/{id_journal_set}', [JournalSetController::class, 'destroy']);
    });
    // end:: journal settings

    // begin:: general ledger
    Route::prefix('general-ledgers')->group(function () {
        Route::get('/', [GeneralLedgerController::class, 'index']);
        Route::get('/complex', [GeneralLedgerController::class, 'complex']);
        Route::get('/statement/{id}', [GeneralLedgerController::class, 'statement']);
        Route::get('/balance', [GeneralLedgerController::class, 'balance']);
        Route::get('/coa/{coa}', [GeneralLedgerController::class, 'coa']);
        Route::get('/transaction-coa', [GeneralLedgerController::class, 'transaction_coa']);
        Route::get('/check', [GeneralLedgerController::class, 'check']);
        Route::put('/update', [GeneralLedgerController::class, 'update']);
        Route::get('/reverse/{type}', [GeneralLedgerController::class, 'reverse']);
    });
    // end:: general ledger

    // begin:: report
    Route::controller(ReportExportController::class)->prefix('export')->group(function () {
        Route::get('/generate_pdf/{id}', 'generate_pdf')->name('generate_pdf');
        Route::get('/asset_depreciation', 'asset_depreciation')->name('asset_depreciation');
        Route::get('/asset_depreciation_excel', 'asset_depreciation_excel')->name('asset_depreciation_excel');
        Route::get('/balance', 'balance')->name('balance');
        Route::get('/ar_control', 'ar_control')->name('ar_control');

        // done optimize
        Route::get('/general_entry', 'journal_umum')->name('journal_umum');
        Route::get('/general_ledger', 'general_ledger')->name('general_ledger');
        Route::get('/journal_interface', 'journal_interface')->name('journal_interface');
    });
    // end:: report

    // begin:: print invoice
    Route::controller(PrintInvoiceController::class)->prefix('print-invoice')->group(function () {
        Route::get('/full-receive', 'full_receive')->name('full_receive');
        Route::get('/outstanding-invoice-receive', 'outstanding_invoice_receive')->name('outstanding_invoice_receive');
        Route::get('/outstanding-receive', 'outstanding_receive')->name('outstanding_receive');
    });
    // end:: print invoice

    // begin:: kartu utang piutang
    Route::controller(KartuUtangPiutangController::class)->prefix('kartu-utang-piutang')->group(function () {
        Route::get('/{type}/{id_kontak?}', 'index');
    });
    // end:: kartu utang piutang

    Route::get('/finance-search', [SearchController::class, 'globalSearch']);
    Route::get('/kurs-usd', [KursController::class, 'index']);
});

// begin:: closing entries
Route::prefix('closing-entries')->group(function () {
    Route::get('/', [ClosingEntryController::class, 'index']);
    Route::post('/', [ClosingEntryController::class, 'store'])->middleware([
        'CheckPreferenceKey:equity_coa,income_summary_coa,retained_earnings_coa,company_category,est_date,coa_pph_badan,coa_pph_pasal_22,coa_pph_pasal_23,coa_pph_pasal_25,coa_utang_pajak_29',
        'CheckCoaPreference:equity_coa,income_summary_coa,retained_earnings_coa,coa_pph_badan,coa_pph_pasal_22,coa_pph_pasal_23,coa_pph_pasal_25,coa_utang_pajak_29'
    ]);
    Route::post('/open', [ClosingEntryController::class, 'open']);
});
// end:: closing entries

// ==============================================

Route::prefix('dashboard')->group(function () {
    Route::prefix('finance')->group(function () {
        Route::get('/transaction', [DashboardController::class, 'transaction']);
        Route::get('/bank-cash', [DashboardController::class, 'bankCash']);
        Route::get('/receive-expense', [DashboardController::class, 'receiveExpense']);
        Route::get('/receivable-payable', [DashboardController::class, 'receivablePayable']);
    });
});

// begin:: coa
Route::prefix('coa')->group(function () {
    // begin:: groups
    Route::get('/groups', [CoaGroupController::class, 'index']);
    Route::post('/groups', [CoaGroupController::class, 'store'])->middleware([
        'CheckPreferenceKey:coa_digit',
    ]);
    Route::get('/groups/{id}', [CoaGroupController::class, 'show']);
    Route::put('/groups/{id}', [CoaGroupController::class, 'update']);
    Route::delete('/groups/{id}', [CoaGroupController::class, 'destroy']);
    // end:: groups

    // begin:: heads
    Route::prefix('heads')->group(function () {
        Route::get('/', [CoaHeadController::class, 'index']);
        Route::post('/', [CoaHeadController::class, 'store']);
        Route::get('/{id}', [CoaHeadController::class, 'show']);
        Route::put('/{id}', [CoaHeadController::class, 'update']);
        Route::delete('/{id}', [CoaHeadController::class, 'destroy']);
        Route::get('/details/{id}', [CoaHeadController::class, 'details']);
    });
    // end:: heads

    // begin:: bodies
    Route::prefix('bodies')->group(function () {
        Route::get('/', [CoaBodyController::class, 'index']);
        Route::post('/', [CoaBodyController::class, 'store']);
        Route::get('/{id}', [CoaBodyController::class, 'show']);
        Route::put('/{id}', [CoaBodyController::class, 'update']);
        Route::delete('/{id}', [CoaBodyController::class, 'destroy']);
        Route::get('/details/{id}', [CoaBodyController::class, 'details']);
    });
    // end:: bodies

    // begin:: clasifications
    Route::prefix('clasifications')->group(function () {
        Route::get('/', [CoaClasificationController::class, 'index']);
        Route::get('/{slug}', [CoaClasificationController::class, 'filter']);
    });
    // end:: clasifications

    // begin:: coa
    Route::prefix('coas')->group(function () {
        Route::get('/', [CoaController::class, 'index']);
        Route::post('/', [CoaController::class, 'store']);
        Route::get('/{id}', [CoaController::class, 'show']);
        Route::put('/{id}', [CoaController::class, 'update']);
        Route::delete('/{id}', [CoaController::class, 'destroy']);
        Route::get('/details/{id}', [CoaController::class, 'details']);
    });
    // end:: coa
});
// end:: coa

// begin:: asset
Route::prefix('asset')->group(function () {
    // begin:: category
    Route::prefix('category')->group(function () {
        Route::get('/', [AssetCategoryController::class, 'index']);
        Route::post('/', [AssetCategoryController::class, 'store']);
        Route::get('/{id}', [AssetCategoryController::class, 'show']);
        Route::put('/{id}', [AssetCategoryController::class, 'update']);
        Route::delete('/{id}', [AssetCategoryController::class, 'destroy']);
    });
    // end:: category

    // begin:: coa
    Route::prefix('coa')->group(function () {
        Route::get('/', [AssetCoaController::class, 'index']);
        Route::post('/', [AssetCoaController::class, 'store']);
        Route::get('/{id}', [AssetCoaController::class, 'show']);
        Route::put('/{id}', [AssetCoaController::class, 'update']);
        Route::delete('/{id}', [AssetCoaController::class, 'destroy']);
    });
    // end:: coa

    // begin:: group
    Route::prefix('group')->group(function () {
        Route::get('/', [AssetGroupController::class, 'index']);
        Route::post('/', [AssetGroupController::class, 'store']);
        Route::get('/{id}', [AssetGroupController::class, 'show']);
        Route::put('/{id}', [AssetGroupController::class, 'update']);
        Route::delete('/{id}', [AssetGroupController::class, 'destroy']);
    });
    // end:: group

    // begin:: item
    Route::prefix('item')->group(function () {
        Route::get('/', [AssetItemController::class, 'index']);
        Route::post('/', [AssetItemController::class, 'store']);
        Route::get('/{id}', [AssetItemController::class, 'show']);
        Route::put('/{id}', [AssetItemController::class, 'update']);
        Route::delete('/{id}', [AssetItemController::class, 'destroy']);
        Route::get('/filter/{id}', [AssetItemController::class, 'filter']);
        Route::get('/not/disposed', [AssetItemController::class, 'assetItemNotDisposed']);
    });
    // end:: item

    // begin:: purchase
    Route::prefix('purchase')->group(function () {
        Route::get('/{is_outstanding}', [AssetPurchaseController::class, 'index']);
        Route::post('/', [AssetPurchaseController::class, 'store']);
    });
    // end:: purchase

    // begin:: disposal
    Route::prefix('disposal')->group(function () {
        Route::get('/', [AssetDisposalController::class, 'index']);
        Route::post('/', [AssetDisposalController::class, 'store']);
    });
    // end:: disposal
});
// end:: asset

// begin:: report
Route::prefix('report')->group(function () {
    // begin:: report_menu
    Route::apiResource('/menus', ReportMenuController::class);
    // end:: report_menu

    // Group dengan middleware untuk titles dan bodies
    Route::middleware(['CheckPreferenceKey:equity_coa', 'CheckCoaPreference:equity_coa'])->group(function () {
        // begin:: report_title
        Route::prefix('titles')->group(function () {
            Route::get('/', [ReportTitleController::class, 'index']);
            Route::post('/', [ReportTitleController::class, 'store']);
            Route::get('/{id}', [ReportTitleController::class, 'show']);
            Route::put('/update-order', [ReportTitleController::class, 'updateOrder']);
            Route::put('/{id}', [ReportTitleController::class, 'update']);
            Route::delete('/{id}', [ReportTitleController::class, 'destroy']);
            Route::get('/details/{id}', [ReportTitleController::class, 'details']);
        });
        // end:: report_title

        // begin:: report_body
        Route::prefix('bodies')->group(function () {
            Route::get('/', [ReportBodyController::class, 'index']);
            Route::post('/', [ReportBodyController::class, 'store']);
            Route::get('/{id}', [ReportBodyController::class, 'show']);
            Route::put('/{id}', [ReportBodyController::class, 'update']);
            Route::delete('/{id}', [ReportBodyController::class, 'destroy']);
            Route::get('/details/{id}', [ReportBodyController::class, 'details']);
        });
        // end:: report_body
    });
});
// end:: report

// begin:: bank n cash
Route::get('/bank-n-cash/search-by-type', [BankNCashController::class, 'searchByType']);
Route::apiResource('/bank-n-cash', BankNCashController::class);
// end:: bank n cash

// begin:: tax
Route::apiResource('/taxs', TaxController::class);
// end:: tax

// begin:: tax rate
Route::prefix('tax-rates')->group(function () {
    Route::get('/', [TaxRateController::class, 'index']);
    Route::post('/', [TaxRateController::class, 'store']);
    Route::get('/{id}', [TaxRateController::class, 'show']);
    Route::put('/{id}', [TaxRateController::class, 'update']);
    Route::delete('/{id}', [TaxRateController::class, 'destroy']);
    Route::get('/group/{id_tax}', [TaxRateController::class, 'group']);
});
// end:: tax rate

// begin:: tax coa
Route::apiResource('/tax-coas', TaxCoaController::class);
// end:: tax coa

// begin:: transaction term
Route::controller(TransactionTermController::class)->prefix('transaction-terms')->group(function () {
    Route::get('/', 'index');
    Route::get('/details', 'detail');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::delete('/{id}', 'destroy');
});
// end:: transaction term

// begin:: transaction tax
Route::prefix('transaction-tax')->group(function () {
    Route::get('/{type}/{id?}', [TransactionTaxController::class, 'index']);
    Route::put('/{type}', [TransactionTaxController::class, 'update']);
});
// end:: transaction tax

// begin:: journal
Route::prefix('journals')->group(function () {
    Route::get('/', [JournalController::class, 'index']);
    Route::post('/', [JournalController::class, 'store']);
    Route::put('/{id}', [JournalController::class, 'update']);
    Route::delete('/{id}', [JournalController::class, 'destroy']);
    Route::get('/filter/{type}/{alocation?}/{is_outstanding?}', [JournalController::class, 'filter']);
    Route::get('filter-outstanding/{type}/{is_outstanding}', [JournalController::class, 'filterWOutstanding']);
});
// end:: journal

// begin:: journal settings
Route::prefix('journal-sets')->group(function () {
    Route::get('/{id_journal}', [JournalSetController::class, 'index']);
    Route::get('/show/{id_journal_set}', [JournalSetController::class, 'show']);
    Route::put('/update/{id_journal_set}', [JournalSetController::class, 'update']);
    Route::delete('destroy/{id_journal_set}', [JournalSetController::class, 'destroy']);
});
// end:: journal settings

// begin:: general ledger
Route::prefix('general-ledgers')->group(function () {
    Route::get('/', [GeneralLedgerController::class, 'index']);
    Route::get('/complex', [GeneralLedgerController::class, 'complex']);
    Route::get('/statement/{id}', [GeneralLedgerController::class, 'statement']);
    Route::get('/balance', [GeneralLedgerController::class, 'balance']);
    Route::get('/coa/{coa}', [GeneralLedgerController::class, 'coa']);
    Route::get('/transaction-coa', [GeneralLedgerController::class, 'transaction_coa']);
    Route::get('/check', [GeneralLedgerController::class, 'check']);
    Route::put('/update', [GeneralLedgerController::class, 'update']);
    Route::get('/reverse/{type}', [GeneralLedgerController::class, 'reverse']);
});
// end:: general ledger

// begin:: report
Route::controller(ReportExportController::class)->prefix('export')->group(function () {
    Route::get('/generate_pdf/{id}', 'generate_pdf')->name('generate_pdf');
    Route::get('/asset_depreciation', 'asset_depreciation')->name('asset_depreciation');
    Route::get('/asset_depreciation_excel', 'asset_depreciation_excel')->name('asset_depreciation_excel');
    Route::get('/balance', 'balance')->name('balance');
    Route::get('/journal_interface', 'journal_interface')->name('journal_interface');
    Route::get('/general_entry', 'journal_umum')->name('journal_umum');
    Route::get('/general_ledger', 'general_ledger')->name('general_ledger');
});
// end:: report

// begin:: print invoice
Route::controller(PrintInvoiceController::class)->prefix('print-invoice')->group(function () {
    Route::get('/full-receive', 'fullReceive')->name('fullReceive');
    Route::get('/full-expense', 'fullExpense')->name('fullExpense');
    Route::get('/outstanding-invoice-receive', 'outstanding_invoice_receive')->name('outstanding_invoice_receive');
    Route::get('/outstanding-receive', 'outstanding_receive')->name('outstanding_receive');
    Route::get('/advance-payment', 'advancePayment')->name('advancePayment');
    Route::get('/down-payment', 'downPayment')->name('downPayment');
});
// end:: print invoice

//begin:: down payment
Route::prefix('down-payments')->group(function () {
    Route::get('/', [DownPaymentsController::class, 'index']);
    Route::get('/kontak/{id_kontak}/{category?}', [DownPaymentsController::class, 'getContactSummary']);
    Route::post('/', [DownPaymentsController::class, 'store'])->middleware([
        'CheckPreferenceKey:down_payment_deposit_journal',
        'CheckJournalPreference:down_payment_deposit_journal'
    ]);
});
//end:: down payment

//begin:: advance payment
Route::prefix('advance-payments')->group(function () {
    Route::get('/', [LiabilityController::class, 'index']);
    Route::get('/kontak/{id_kontak}/{category?}', [LiabilityController::class, 'getContactSummary']);
    Route::post('/', [LiabilityController::class, 'store'])->middleware([
        'CheckPreferenceKey:advance_payment_deposit_journal',
        'CheckJournalPreference:advance_payment_deposit_journal'
    ]);
});
//end:: advance payment

// begin:: kartu utang piutang
Route::controller(KartuUtangPiutangController::class)->prefix('kartu-utang-piutang')->group(function () {
    Route::get('/{type}/{id_kontak?}', 'index');
});
// end:: kartu utang piutang

Route::get('/finance-search', [SearchController::class, 'globalSearch']);
Route::get('/kurs-usd', [KursController::class, 'index']);

// begin:: invoice-bill
Route::controller(InvoiceBillController::class)->prefix('invoice-bills')->group(function () {
    Route::get('/', 'index');
    Route::get('/details', 'details');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
    Route::put('/actual/{id}', 'actual');
    Route::delete('/{id}', 'destroy');
});
// end:: invoice-bill