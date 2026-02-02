<?php

use App\Http\Controllers\api\contract_legal\KasusLController;
use App\Http\Controllers\api\contract_legal\ReviewController;
use App\Http\Controllers\api\contract_legal\RevisiController;
use App\Http\Controllers\api\contract_legal\KasusNlController;
use App\Http\Controllers\api\contract_legal\KontrakController;
use App\Http\Controllers\api\contract_legal\DraftingController;
use App\Http\Controllers\api\contract_legal\TahapanKController;
use App\Http\Controllers\api\contract_legal\TahapanLController;
use App\Http\Controllers\api\contract_legal\PengajuanController;
use App\Http\Controllers\api\contract_legal\TahapanNlController;
use App\Http\Controllers\api\contract_legal\FinalDraftController;
use App\Http\Controllers\api\contract_legal\JadwalSidangController;
use App\Http\Controllers\api\contract_legal\KasusRiwayatLController;
use App\Http\Controllers\api\contract_legal\KasusRiwayatNlController;
use App\Http\Controllers\api\contract_legal\KontrakTahapanController;
use Illuminate\Support\Facades\Route;

// begin:: tahapan_l
Route::prefix('tahapan_l')->group(function () {
    Route::get('/', [TahapanLController::class, 'index']);
    Route::post('/', [TahapanLController::class, 'store']);
    Route::get('/{id}', [TahapanLController::class, 'show']);
    Route::put('/{id}', [TahapanLController::class, 'update']);
    Route::delete('/{id}', [TahapanLController::class, 'destroy']);
    Route::get('/category/{category}', [TahapanLController::class, 'category']);
});
// end:: tahapan_l

// begin:: kasus_l
Route::prefix('kasus_l')->group(function () {
    Route::get('/', [KasusLController::class, 'index']);
    Route::post('/', [KasusLController::class, 'store']);
    Route::get('/{id}', [KasusLController::class, 'show']);
    Route::put('/{id}', [KasusLController::class, 'update']);
    Route::delete('/{id}', [KasusLController::class, 'destroy']);
    Route::post('/cabut/{id}', [KasusLController::class, 'cabut']);
});
// end:: kasus_l

// begin:: kasus_riwayat_l
Route::prefix('kasus_riwayat_l')->group(function () {
    Route::get('/', [KasusRiwayatLController::class, 'index']);
    Route::post('/', [KasusRiwayatLController::class, 'store']);
    Route::get('/{id}', [KasusRiwayatLController::class, 'show']);
    Route::get('/detail/{id}', [KasusRiwayatLController::class, 'detail']);
    Route::post('/jadwal', [KasusRiwayatLController::class, 'jadwal']);
});
// end:: kasus_riwayat_l

// begin:: tahapan_nl
Route::apiResource('/tahapan_nl', TahapanNlController::class);
// end:: tahapan_nl

// begin:: tahapan_k
Route::apiResource('/tahapan_k', TahapanKController::class);
// end:: tahapan_k

// begin:: kasus_nl
Route::prefix('kasus_nl')->group(function () {
    Route::get('/', [KasusNlController::class, 'index']);
    Route::post('/', [KasusNlController::class, 'store']);
    Route::get('/{id}', [KasusNlController::class, 'show']);
    Route::put('/{id}', [KasusNlController::class, 'update']);
    Route::delete('/{id}', [KasusNlController::class, 'destroy']);
    Route::post('/transfer', [KasusNlController::class, 'transfer']);
    Route::post('/cabut/{id}', [KasusNlController::class, 'cabut']);
});
// end:: kasus_nl

// begin:: kasus_riwayat_nl
Route::prefix('kasus_riwayat_nl')->group(function () {
    Route::get('/', [KasusRiwayatNlController::class, 'index']);
    Route::post('/', [KasusRiwayatNlController::class, 'store']);
    Route::get('/{id}', [KasusRiwayatNlController::class, 'show']);
    Route::get('/detail/{id}', [KasusRiwayatNlController::class, 'detail']);
});
// end:: kasus_riwayat_nl

// begin:: jadwal_sidang
Route::prefix('jadwal_sidang')->group(function () {
    Route::get('/', [JadwalSidangController::class, 'index']);
    Route::post('/', [JadwalSidangController::class, 'store']);
    Route::get('/{id}', [JadwalSidangController::class, 'show']);
    Route::put('/{id}', [JadwalSidangController::class, 'update']);
    Route::post('/cabut/{id}', [JadwalSidangController::class, 'cabut']);
});
// end:: jadwal_sidang

// begin:: kontrak
Route::prefix('kontrak')->group(function () {
    Route::get('/', [KontrakController::class, 'index']);
    Route::get('/unassigned', [KontrakController::class, 'unassigned']);
    Route::post('/', [KontrakController::class, 'store']);
    Route::get('/{id}', [KontrakController::class, 'show']);
    Route::put('/{id}', [KontrakController::class, 'update']);
    Route::delete('/{id}', [KontrakController::class, 'destroy']);
    Route::post('/addendum/{id}', [KontrakController::class, 'addendum']);
    Route::put('/renew/{id}', [KontrakController::class, 'renew']);
    Route::post('/add_support_doc/{id}', [KontrakController::class, 'addSupportDoc']);
});
// end:: kontrak

// vbegin:: kontrak_tahapan
Route::prefix('kontrak_tahapan')->group(function () {
    Route::get('/', [KontrakTahapanController::class, 'index']);
    Route::post('/', [KontrakTahapanController::class, 'store']);
    Route::get('/{id}', [KontrakTahapanController::class, 'show']);
    Route::put('/{id}', [KontrakTahapanController::class, 'update']);
    Route::delete('/{id}', [KontrakTahapanController::class, 'destroy']);
    Route::post('/approve/{id}/{id_tahapan_k}', [KontrakTahapanController::class, 'approve']);
    Route::post('/approve_addendum/{id}', [KontrakTahapanController::class, 'approve_addendum']);
    Route::post('/reject/{id}', [KontrakTahapanController::class, 'reject']);
    Route::post('/finalize/{id}', [KontrakTahapanController::class, 'finalize']);
    Route::put('/finalize_addendum/{id}', [KontrakTahapanController::class, 'finalize_addendum']);
    Route::put('/edit_desc/{id}', [KontrakTahapanController::class, 'edit_desc']);
});
// end:: kontrak_tahapan

// begin:: pengajuan
Route::prefix('pengajuan')->group(function () {
    Route::get('/', [PengajuanController::class, 'index']);
    Route::post('/', [PengajuanController::class, 'store']);
    Route::get('/{id}', [PengajuanController::class, 'show']);
    Route::put('/{id}', [PengajuanController::class, 'update']);
    Route::delete('/{id}', [PengajuanController::class, 'destroy']);
});
// end:: pengajuan   

// begin:: drafting
Route::prefix('drafting')->group(function () {
    Route::get('/', [DraftingController::class, 'index']);
    Route::post('/', [DraftingController::class, 'store']);
    Route::get('/{id}', [DraftingController::class, 'show']);
    Route::put('/{id}', [DraftingController::class, 'update']);
    Route::delete('/{id}', [DraftingController::class, 'destroy']);
});
// end:: drafting   

// begin:: review
Route::prefix('review')->group(function () {
    Route::get('/', [ReviewController::class, 'index']);
    Route::post('/', [ReviewController::class, 'store']);
    Route::get('/{id}', [ReviewController::class, 'show']);
    Route::put('/{id}', [ReviewController::class, 'update']);
    Route::delete('/{id}', [ReviewController::class, 'delete']);
});
// end:: review

// begin final_draft
Route::prefix('final_draft')->group(function () {
    Route::get('/', [FinalDraftController::class, 'index']);
    Route::post('/', [FinalDraftController::class, 'store']);
    Route::get('/{id}', [FinalDraftController::class, 'show']);
    Route::put('/{id}', [FinalDraftController::class, 'update']);
    Route::delete('/{id}', [FinalDraftController::class, 'destroy']);
});
// end final_draft

// begin:: revisi
Route::prefix('revisi')->group(function () {
    Route::get('/', [RevisiController::class, 'index']);
    Route::post('/', [RevisiController::class, 'store']);
    Route::get('/{id}', [RevisiController::class, 'show']);
    Route::put('/{id}', [RevisiController::class, 'update']);
    Route::delete('/{id}', [RevisiController::class, 'destroy']);
});
// end:: revisi