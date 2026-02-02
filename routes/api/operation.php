<?php

use App\Http\Controllers\api\operation\DashboardController;
use App\Http\Controllers\api\operation\BlockController;
use App\Http\Controllers\api\operation\CogController;
use App\Http\Controllers\api\operation\DomEfoController;
use App\Http\Controllers\api\operation\DomEtoController;
use App\Http\Controllers\api\operation\DomInPitController;
use App\Http\Controllers\api\operation\DrillingController;
use App\Http\Controllers\api\operation\InfrastructureController;
use App\Http\Controllers\api\operation\InvoiceFobController;
use App\Http\Controllers\api\operation\IupAreaController;
use App\Http\Controllers\api\operation\JettyController;
use App\Http\Controllers\api\operation\KontraktorController;
use App\Http\Controllers\api\operation\KontraktorUsersController;
use App\Http\Controllers\api\operation\MapPrintController;
use App\Http\Controllers\api\operation\OreShippingController;
use App\Http\Controllers\api\operation\PitController;
use App\Http\Controllers\api\operation\PitTestController;
use App\Http\Controllers\api\operation\PlanBargingController;
use App\Http\Controllers\api\operation\PrintPDFController;
use App\Http\Controllers\api\operation\ProvisionCoaController;
use App\Http\Controllers\api\operation\ProvisionController;
use App\Http\Controllers\api\operation\ReportExportController;
use App\Http\Controllers\api\operation\ShippingInstructionApproveController;
use App\Http\Controllers\api\operation\ShippingInstructionController;
use App\Http\Controllers\api\operation\SlotController;
use App\Http\Controllers\api\operation\StokEfoController;
use App\Http\Controllers\api\operation\StokEtoController;
use App\Http\Controllers\api\operation\StokGlobalController;
use App\Http\Controllers\api\operation\StokInPitController;
use App\Http\Controllers\api\operation\StokPsiController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->group(function () {
    Route::get('/contractor-summary', [DashboardController::class, 'contractor_summary']);
    Route::get('/product-shipping', [DashboardController::class, 'product_shipping']);
    Route::get('/product-inventory', [DashboardController::class, 'product_inventory']);
    Route::get('/product-grade', [DashboardController::class, 'product_grade']);
});

// begin:: kontraktor
Route::apiResource('/kontraktors', KontraktorController::class);
Route::patch('/kontraktors-color/{id}', [KontraktorController::class, 'updateColor']);
// end:: knntraktor

// begin:: block
Route::prefix('blocks')->group(function () {
    Route::get('/list', [BlockController::class, 'list']);
    Route::get('/', [BlockController::class, 'index']);
    Route::post('/', [BlockController::class, 'store']);
    Route::get('/{id}', [BlockController::class, 'show']);
    Route::put('/{id}', [BlockController::class, 'update']);
    Route::delete('/{id}', [BlockController::class, 'destroy']);
    Route::get('/maps/{id_kontraktor}', [BlockController::class, 'maps']);
});
// end:: block

// begin:: pit_test
Route::prefix('pit_tests')->group(function () {
    Route::get('/', [PitTestController::class, 'index']);
    Route::post('/', [PitTestController::class, 'store']);
    Route::get('/{id}', [PitTestController::class, 'show']);
    Route::put('/{id}', [PitTestController::class, 'update']);
    Route::delete('/{id}', [PitTestController::class, 'destroy']);
    Route::get('/maps/{id_kontraktor}', [PitTestController::class, 'maps']);
});
// end:: pit_test

// begin:: drilling
Route::prefix('drillings')->group(function () {
    Route::get('/', [DrillingController::class, 'index']);
    Route::post('/', [DrillingController::class, 'store']);
    Route::get('/{id}', [DrillingController::class, 'show']);
    Route::put('/{id}', [DrillingController::class, 'update']);
    Route::delete('/{id}', [DrillingController::class, 'destroy']);
    Route::get('/maps/{id_kontraktor}', [DrillingController::class, 'maps']);
});
// end:: drilling

// begin:: jetty
Route::prefix('jetties')->group(function () {
    Route::get('/', [JettyController::class, 'index']);
    Route::post('/', [JettyController::class, 'store']);
    Route::get('/{id}', [JettyController::class, 'show']);
    Route::put('/{id}', [JettyController::class, 'update']);
    Route::delete('/{id}', [JettyController::class, 'destroy']);
    Route::get('/maps/{id_kontraktor}', [JettyController::class, 'maps']);
});
// end:: jetty

// begin:: infrastructures
Route::prefix('infrastructures')->group(function () {
    Route::get('/self', [InfrastructureController::class, 'self']);
    Route::get('/', [InfrastructureController::class, 'index']);
    Route::post('/', [InfrastructureController::class, 'store']);
    Route::get('/{id}', [InfrastructureController::class, 'show']);
    Route::put('/{id}', [InfrastructureController::class, 'update']);
    Route::delete('/{id}', [InfrastructureController::class, 'destroy']);
    Route::get('/maps/{id_kontraktor}', [InfrastructureController::class, 'maps']);
    Route::get('/filter/{category}', [InfrastructureController::class, 'filter']);
});
// end:: infrastructures

// begin:: iup_areas
Route::prefix('iup_areas')->group(function () {
    Route::get('/maps', [IupAreaController::class, 'maps']);
    Route::get('/', [IupAreaController::class, 'index']);
    Route::post('/', [IupAreaController::class, 'store']);
    Route::get('/{id}', [IupAreaController::class, 'show']);
    Route::put('/{id}', [IupAreaController::class, 'update']);
    Route::delete('/{id}', [IupAreaController::class, 'destroy']);
});
// end:: iup_areas

// begin:: shipping_instruction
Route::prefix('shipping_instructions')->group(function () {
    Route::get('/timeline', [ShippingInstructionController::class, 'timeline']);
    Route::get('/{status?}', [ShippingInstructionController::class, 'index']);
    Route::get('/detail/{id}', [ShippingInstructionController::class, 'show']);
    Route::post('/', [ShippingInstructionController::class, 'store']);
    Route::put('/{id}', [ShippingInstructionController::class, 'update']);
    Route::put('/approve/{id}', [ShippingInstructionController::class, 'approve']);
    Route::put('/rejected/{id}', [ShippingInstructionController::class, 'rejected']);
    Route::delete('/{id}', [ShippingInstructionController::class, 'destroy']);
});
// end:: shipping_instruction

// begin:: shipping_instruction_approve
Route::prefix('shipping_instructions_approve')->group(function () {
    Route::get('/', [ShippingInstructionApproveController::class, 'index']);
    Route::post('/{status}', [ShippingInstructionApproveController::class, 'store']);
});
// end:: shipping_instruction_approve

// begin:: cog
Route::apiResource('/cogs', CogController::class);
// end:: cog

// begin:: dom_etos
Route::apiResource('/dom_etos', DomEtoController::class);
// end:: dom_etos

// begin:: dom_efos
Route::apiResource('/dom_efos', DomEfoController::class);
// end:: dom_efos

// begin:: dom_in_pits
Route::apiResource('/dom_in_pits', DomInPitController::class);
// end:: dom_in_pits

// begin:: pits
Route::apiResource('/pits', PitController::class);
// end:: pits

// begin:: slots
Route::apiResource('/slots', SlotController::class);
// end:: slots

// begin:: kontraktor_users
Route::apiResource('/kontraktor_users', KontraktorUsersController::class);
// end:: kontraktor_users

// begin:: stok_in_pits
Route::prefix('stok_in_pits')->group(function () {
    Route::get('/', [StokInPitController::class, 'index']);
    Route::post('/', [StokInPitController::class, 'store']);
    Route::post('/transfer', [StokInPitController::class, 'transfer']);
    Route::put('/{id}', [StokInPitController::class, 'update']);
    Route::get('/filter', [StokInPitController::class, 'filter']);
    Route::post('/upload', [StokInPitController::class, 'upload']);
    Route::get('/download', [StokInPitController::class, 'download']);
    Route::delete('/{id}', [StokInPitController::class, 'destroy']);
});
// end:: stok_in_pits

// begin:: stok_etos
Route::prefix('stok_etos')->group(function () {
    Route::get('/', [StokEtoController::class, 'index']);
    Route::post('/', [StokEtoController::class, 'store']);
    Route::post('/transfer', [StokEtoController::class, 'transfer']);
    Route::put('/{id}', [StokEtoController::class, 'update']);
    Route::delete('/{id}', [StokEtoController::class, 'destroy']);
    Route::get('/details/{id}', [StokEtoController::class, 'details']);
    Route::get('/generate', [StokEtoController::class, 'generate']);
});
// end:: stok_etos

// begin:: stok_efos
Route::prefix('stok_efos')->group(function () {
    Route::get('/', [StokEfoController::class, 'index']);
    Route::post('/', [StokEfoController::class, 'store']);
    Route::post('/transfer', [StokEfoController::class, 'transfer']);
    Route::put('/{id}', [StokEfoController::class, 'update']);
    Route::delete('/{id}', [StokEfoController::class, 'destroy']);
    Route::get('/details/{id}', [StokEfoController::class, 'details']);
    Route::get('/generate', [StokEfoController::class, 'generate']);
});
// end:: stok_efos

// begin:: stok_psis
Route::prefix('stok_psis')->group(function () {
    Route::get('/', [StokPsiController::class, 'index']);
    Route::post('/', [StokPsiController::class, 'store']);
});
// end:: stok_psis

// begin:: plan_barging
Route::prefix('plan_barging')->group(function () {
    Route::get('/', [PlanBargingController::class, 'index']);
    Route::post('/', [PlanBargingController::class, 'store']);
    Route::get('/details/{id}', [PlanBargingController::class, 'details']);
    Route::delete('/{id}', [PlanBargingController::class, 'destroy']);
});
// end:: plan_barging

// begin:: stok_globals
Route::prefix('stok_globals')->group(function () {
    Route::get('/', [StokGlobalController::class, 'index']);
});
// end:: stok_globals

// begin:: provision
Route::prefix('provision')->group(function () {
    Route::get('/', [ProvisionController::class, 'index']);
    Route::post('/', [ProvisionController::class, 'store']);
    Route::put('/{id}', [ProvisionController::class, 'update']); // update dsr, pnbp prov, coa, pnbp final
});
// end:: provision

// begin:: provision_coa
Route::prefix('provision_coa')->group(function () {
    Route::get('/', [ProvisionCoaController::class, 'index']);
    Route::post('/', [ProvisionCoaController::class, 'store']);
    Route::put('/{id}', [ProvisionCoaController::class, 'update']); // update add pnbp final
});
// end:: provision_coa

// begin:: invoice_fob
Route::controller(InvoiceFobController::class)->prefix('invoice_fob')->group(function () {
    Route::get('/', 'index');
    Route::get('/check/{transaction_number}', 'check');
    Route::get('/{id}', 'show');
    Route::post('/', 'store');
    Route::put('/{id}', 'update');
});
// end:: invoice_fob

// begin:: print pdf
Route::controller(PrintPDFController::class)->prefix('pdf')->group(function () {
    Route::get('/shipping_instruction', 'shipping_instruction')->name('shipping_instruction');
    Route::get('/invoice_provision', 'invoice_provision')->name('invoice_provision');
    // Route::get('/invoice-bill', 'invoiceBill')->name('invoiceBill');
    Route::get('/invoice-bill', 'invoiceBill');
    Route::get('/invoice-bill-draft', 'invoiceBillDraft');
});
// end:: print pdf

// begin:: ore_shipping
Route::prefix('ore_shipping')->group(function () {
    Route::get('/', [OreShippingController::class, 'index']);
});
// end:: ore_shipping

Route::controller(ReportExportController::class)->prefix('export')->group(function () {
    Route::get('/ore_shipping_all', 'all')->name('all');
    Route::get('/product_shipping', 'product_shipping')->name('product_shipping');
    Route::get('/contractor', 'contractor')->name('contractor');
    Route::get('/contact', 'contact')->name('contact');
});

Route::controller(MapPrintController::class)->group(function () {
    Route::post('/map-print', 'all')->name('all');
});
