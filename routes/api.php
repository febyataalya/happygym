<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\InstrukturApiController;

// ==========================================
// ROUTE API UNTUK MEMBER
// ==========================================

Route::post('/midtrans/callback', [MemberApiController::class, 'midtransCallback']);

Route::prefix('member')->group(function () {
    // 1. Auth & Profil (Poin 2, 3, 4, 11)
    Route::post('/register', [\App\Http\Controllers\Api\MemberApiController::class, 'register']);
    Route::get('/lokasis', [\App\Http\Controllers\Api\MemberApiController::class, 'getLokasis']);
    Route::post('/login', [\App\Http\Controllers\Api\MemberApiController::class, 'login']);
    Route::get('/profile/{id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getProfile']);
    Route::post('/profile/{id}', [\App\Http\Controllers\Api\MemberApiController::class, 'updateProfile']);
    Route::get('/data-fisik/{id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getDataFisik']);
    Route::post('/data-fisik/{id}', [\App\Http\Controllers\Api\MemberApiController::class, 'simpanDataFisik']);
    Route::get('/pengumuman', [\App\Http\Controllers\Api\MemberApiController::class, 'getPengumuman']);

    // 2. Transaksi & Master Data (Poin 5 & 6)
    Route::get('/pakets', [\App\Http\Controllers\Api\MemberApiController::class, 'getPakets']);
    Route::get('/daftar-instruktur', [\App\Http\Controllers\Api\MemberApiController::class, 'getDaftarInstruktur']);
    Route::post('/beli-paket', [\App\Http\Controllers\Api\MemberApiController::class, 'beliPaket']);

    // 3. Kelas Gym Umum
    Route::get('/jadwal-gym', [\App\Http\Controllers\Api\MemberApiController::class, 'getJadwalGymUmum']);
    Route::post('/booking-gym', [\App\Http\Controllers\Api\MemberApiController::class, 'bookingGymUmum']);

    // 4. Personal Trainer (PT) (Poin 7)
    Route::get('/pt/paket-aktif/{member_id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getPaketPtAktif']);
    Route::get('/pt/instruktur/{member_id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getInstrukturPtTersedia']);
    Route::post('/pt/booking', [\App\Http\Controllers\Api\MemberApiController::class, 'bookingPt']);
    Route::post('/pt/tanggapan-negosiasi', [\App\Http\Controllers\Api\MemberApiController::class, 'tanggapanNegosiasiPt']);
    Route::get('/pt/instruktur/{id}/kalender', [MemberApiController::class, 'kalenderInstruktur']);

    // 5. Riwayat (Poin 10)
    Route::get('/riwayat-pembayaran/{member_id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getRiwayatPembayaran']);
    Route::get('/riwayat-latihan/{member_id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getRiwayatLatihan']);
    Route::get('/pt/riwayat-booking/{member_id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getRiwayatBookingPt']);
    Route::get('/pt/riwayat-sesi/{member_id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getRiwayatSesiPt']);
    Route::post('/pt/rate-instruktur', [\App\Http\Controllers\Api\MemberApiController::class, 'rateInstruktur']);

    Route::get('/pt/coach-cabang/{member_id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getCoachCabang']);
    Route::post('/pt/pilih-coach', [\App\Http\Controllers\Api\MemberApiController::class, 'pilihCoachPt']);

    // 6. Presensi Mandiri Gedung
    Route::get('/presensi/status/{member_id}', [\App\Http\Controllers\Api\MemberApiController::class, 'getStatusPresensi']);
    Route::post('/presensi/check-in', [\App\Http\Controllers\Api\MemberApiController::class, 'checkIn']);
    Route::post('/presensi/check-out', [\App\Http\Controllers\Api\MemberApiController::class, 'checkOut']);

    Route::post('/pt/rate-instruktur', [MemberApiController::class, 'rateInstruktur']);
});

// ==========================================
// ROUTE API UNTUK INSTRUKTUR
// ==========================================
Route::prefix('instruktur')->group(function () {
    // --- FITUR LAMA ---
    Route::post('/login', [InstrukturApiController::class, 'login']);
    Route::get('/profile/{id}', [InstrukturApiController::class, 'getProfile']);
    Route::post('/profile/update/{id}', [InstrukturApiController::class, 'updateProfile']);
    Route::get('/jadwal-saya/{instruktur_id}', [InstrukturApiController::class, 'getJadwalSaya']);
    Route::get('/daftar-booking/{instruktur_id}', [InstrukturApiController::class, 'getDaftarBooking']);
    Route::post('/scan-absensi', [InstrukturApiController::class, 'scanAbsensi']);
    Route::get('/pengumuman', [InstrukturApiController::class, 'getPengumuman']);

    // --- FITUR BARU: PERSONAL TRAINER (PT) ---
    // 1. Instruktur set jadwal kosong mereka
    Route::post('/pt/set-jadwal', [InstrukturApiController::class, 'setJadwalLuangPt']);
    // 2. Instruktur melihat siapa saja member PT yang harus dilatih (Jadwal disetujui) [Dipertahankan untuk referensi]
    Route::get('/pt/jadwal/{instruktur_id}', [InstrukturApiController::class, 'getJadwalMelatihPt']);
    // 2a. Riwayat Melatih PT Umum (Option A)
    Route::get('/pt/riwayat-umum/{instruktur_id}', [InstrukturApiController::class, 'getRiwayatMelatihUmum']);
    // 2c. Riwayat Melatih per Klien (Option B)
    Route::get('/pt/daftar-klien-riwayat/{instruktur_id}', [InstrukturApiController::class, 'getDaftarKlienDenganRiwayat']);
    // 2b. Instruktur melihat request jadwal masuk
    Route::get('/pt/request/{instruktur_id}', [InstrukturApiController::class, 'getDaftarRequestPt']);
    Route::post('/pt/tanggapan-request', [InstrukturApiController::class, 'tanggapiRequestPt']);
    // 3. Instruktur scan QR code booking PT milik member
    Route::post('/pt/scan-qr', [InstrukturApiController::class, 'scanQrPt']);
    Route::get('/pt/ketersediaan/{instruktur_id}', [InstrukturApiController::class, 'getKetersediaanPt']);
    Route::post('/pilih-instruktur', [MemberApiController::class, 'pilihInstruktur']);
    Route::get('/pt/rating/{instruktur_id}', [InstrukturApiController::class, 'getRatingSaya']);

});