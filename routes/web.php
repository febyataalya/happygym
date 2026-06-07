<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\InstrukturController;
use App\Http\Controllers\PaketController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiskonController;

Route::get('/', [LandingController::class, 'index'])->name('landing.index');
Route::get('/promo/{id}', [LandingController::class, 'showPromo'])->name('promo.show');
Route::get('/download', function() { return view('download'); })->name('download');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('member/export/excel', [MemberController::class, 'exportExcel'])->name('member.export.excel');
Route::get('member/export/pdf', [MemberController::class, 'exportPdf'])->name('member.export.pdf');
Route::resource('member', MemberController::class);
Route::get('instruktur/{id}/clients', [InstrukturController::class, 'clients'])->name('instruktur.clients');
Route::resource('instruktur', InstrukturController::class);
Route::resource('paket', PaketController::class);
Route::resource('pengumuman', PengumumanController::class);
Route::resource('diskon', DiskonController::class);
Route::get('/transaksi/export/excel', [TransaksiController::class, 'exportExcel'])->name('transaksi.export.excel');
Route::get('/transaksi/export/pdf', [TransaksiController::class, 'exportPdf'])->name('transaksi.export.pdf');
Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
Route::get('/transaksi/{id}', [TransaksiController::class, 'show'])->name('transaksi.show');

// Laporan
Route::redirect('/laporan', '/laporan/member');
Route::get('/laporan/member', [\App\Http\Controllers\LaporanController::class, 'memberIndex'])->name('laporan.member');
Route::get('/laporan/member/export/excel', [\App\Http\Controllers\LaporanController::class, 'exportMemberExcel'])->name('laporan.member.export.excel');
Route::get('/laporan/member/export/pdf', [\App\Http\Controllers\LaporanController::class, 'exportMemberPdf'])->name('laporan.member.export.pdf');

Route::get('/laporan/transaksi', [\App\Http\Controllers\LaporanController::class, 'transaksiIndex'])->name('laporan.transaksi');
Route::get('/laporan/transaksi/export/excel', [\App\Http\Controllers\LaporanController::class, 'exportTransaksiExcel'])->name('laporan.transaksi.export.excel');
Route::get('/laporan/transaksi/export/pdf', [\App\Http\Controllers\LaporanController::class, 'exportTransaksiPdf'])->name('laporan.transaksi.export.pdf');

Route::get('/laporan/pt', [\App\Http\Controllers\LaporanController::class, 'ptIndex'])->name('laporan.pt');
Route::get('/laporan/pt/export/excel', [\App\Http\Controllers\LaporanController::class, 'exportPtExcel'])->name('laporan.pt.export.excel');
Route::get('/laporan/pt/export/pdf', [\App\Http\Controllers\LaporanController::class, 'exportPtPdf'])->name('laporan.pt.export.pdf');

Route::get('/laporan/kehadiran', [\App\Http\Controllers\LaporanController::class, 'kehadiranIndex'])->name('laporan.kehadiran');
Route::get('/laporan/kehadiran/export/excel', [\App\Http\Controllers\LaporanController::class, 'exportKehadiranExcel'])->name('laporan.kehadiran.export.excel');
Route::get('/laporan/kehadiran/export/pdf', [\App\Http\Controllers\LaporanController::class, 'exportKehadiranPdf'])->name('laporan.kehadiran.export.pdf');
Route::resource('lokasi', LokasiController::class);
require __DIR__.'/auth.php';
