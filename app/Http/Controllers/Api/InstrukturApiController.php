<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instruktur;
use App\Models\Member;
use App\Models\Presensi;
use App\Models\JadwalLatihan;
use App\Models\BookingAbsensi;
use App\Models\InstrukturRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// --- MODEL TAMBAHAN UNTUK FITUR PT ---
use App\Models\KetersediaanInstruktur;
use App\Models\BookingPt;
use Carbon\Carbon;

class InstrukturApiController extends Controller
{
    // ==========================================
    // BAGIAN 1: FITUR GYM UMUM (KODE LAMA ANDA)
    // ==========================================

    public function login(Request $request)
    {
        $instruktur = Instruktur::where('username', $request->username)->first();
        if (!$instruktur || !Hash::check($request->password, $instruktur->password)) {
            return response()->json(['status' => 'error', 'message' => 'Login Gagal'], 401);
        }
        return response()->json(['status' => 'success', 'data' => $instruktur], 200);
    }

    public function updateProfile(Request $request, $id)
    {
        $instruktur = Instruktur::find($id);
        if (!$instruktur) return response()->json(['status' => 'error', 'message' => 'Instruktur tidak ditemukan'], 404);

        $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:instrukturs,username,'.$id.',instruktur_id',
            'spesialisasi' => 'nullable|string|max:255',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $instruktur->nama = $request->nama;
        $instruktur->username = $request->username;
        $instruktur->spesialisasi = $request->spesialisasi;
        
        if ($request->filled('password')) {
            $instruktur->password = Hash::make($request->password);
        }

        if ($request->hasFile('foto')) {
            if ($instruktur->foto && \Storage::disk('public')->exists($instruktur->foto)) {
                \Storage::disk('public')->delete($instruktur->foto);
            }
            $instruktur->foto = $request->file('foto')->store('instrukturs', 'public');
        }

        $instruktur->save();

        return response()->json(['status' => 'success', 'message' => 'Profil diperbarui!', 'data' => $instruktur], 200);
    }

    public function getPengumuman()
    {
        $data = \App\Models\Pengumuman::where('tampil_instruktur', true)
            ->orderBy('tanggal_post', 'desc')
            ->get();
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function getProfile($id)
    {

    // =========================
    // AMBIL DATA INSTRUKTUR
    // =========================

    $instruktur = Instruktur::find($id);

    if (!$instruktur) {

        return response()->json([
            'status' => 'error',
            'message' => 'Instruktur tidak ditemukan'
        ], 404);
    }

    // =========================
    // HITUNG RATA-RATA RATING
    // =========================

    $rating = round(
        InstrukturRating::where(
            'instruktur_id',
            $instruktur->instruktur_id
        )->avg('rating'),
        1
    );

    // jika belum ada rating
    if (!$rating) {
        $rating = 0;
    }

    // =========================
    // TAMBAHKAN KE RESPONSE
    // =========================

    $instruktur->rating = $rating;

    // =========================
    // RESPONSE
    // =========================

    return response()->json([
        'status' => 'success',
        'data' => $instruktur
    ], 200);
}
    }

    public function getJadwalSaya($instruktur_id)
    {
        $jadwal = JadwalLatihan::where('instruktur_id', $instruktur_id)->get();
        return response()->json(['status' => 'success', 'data' => $jadwal], 200);
    }

    public function getDaftarBooking($instruktur_id)
    {
        $booking = BookingAbsensi::with('member')
                    ->where('instruktur_id', $instruktur_id)
                    ->where('tanggal', now()->toDateString())
                    ->get();
        return response()->json(['status' => 'success', 'data' => $booking], 200);
    }

    public function scanAbsensi(Request $request)
    {
        $request->validate([
            'instruktur_id' => 'required',
            'member_id' => 'required',
        ]);

        $member = Member::find($request->member_id);

        if (!$member || $member->status_membership !== 'Aktif') {
            return response()->json(['status' => 'error', 'message' => 'Member tidak aktif/tidak ditemukan'], 400);
        }

        $presensi = Presensi::create([
            'member_id' => $request->member_id,
            'instruktur_id' => $request->instruktur_id,
            'waktu_presensi' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Presensi berhasil'], 200);
    }


    // ==========================================
    // BAGIAN 2: FITUR PERSONAL TRAINER (BARU)
    // ==========================================

    // 1. Instruktur menentukan hari dan jam luang untuk PT
    public function setJadwalLuangPt(Request $request)
    {
        $request->validate([
            'instruktur_id' => 'required|exists:instrukturs,instruktur_id',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);

        $jadwal = KetersediaanInstruktur::create([
            'instruktur_id' => $request->instruktur_id,
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'is_booked' => false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Jadwal luang PT berhasil ditambahkan!',
            'data' => $jadwal
        ]);
    }

    // 2. Instruktur melihat jadwal melatih PT (Yang sudah disetujui)
    public function getJadwalMelatihPt($instruktur_id)
    {
        // Tarik semua Klien (Member) yang memilih instruktur ini
        $klien = \App\Models\MemberPaketPt::with([
            'member', // Ambil data membernya
            'bookingPts' => function($query) {
                $query->whereIn('status', ['Approved', 'Hadir'])
                      ->where('tanggal_sesi', '>=', \Carbon\Carbon::now()->toDateString())
                      ->orderBy('tanggal_sesi', 'asc')
                      ->orderBy('jam_sesi', 'asc');
            }
        ])
        ->where('instruktur_id', $instruktur_id)
        ->where('status', 'Aktif')
        ->get();

        return response()->json(['status' => 'success', 'data' => $klien], 200);
    }

    // 2b. Instruktur melihat request Booking PT (Pending / Negotiating)
    public function getDaftarRequestPt($instruktur_id)
    {
        $requests = BookingPt::with('memberPaket.member')
            ->where('instruktur_id', $instruktur_id)
            ->whereIn('status', ['Pending', 'Negotiating'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['status' => 'success', 'data' => $requests], 200);
    }

    // 2c. Instruktur memberikan tanggapan (Approve / Reject & Negotiate)
    public function tanggapiRequestPt(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:booking_pts,booking_id',
            'tindakan' => 'required|in:terima,tolak'
        ]);

        $booking = BookingPt::find($request->booking_id);
        
        if ($booking->status !== 'Pending') {
            return response()->json(['status' => 'error', 'message' => 'Status booking bukan Pending.'], 400);
        }

        if ($request->tindakan === 'terima') {
            $booking->status = 'Approved';
            $booking->save();
            return response()->json(['status' => 'success', 'message' => 'Booking disetujui.']);
        } else {
            // Tolak dan berikan saran negosiasi
            $request->validate([
                'alasan_penolakan' => 'required',
                'saran_tanggal' => 'required|date',
                'saran_jam' => 'required'
            ]);
            $booking->status = 'Negotiating';
            $booking->alasan_penolakan = $request->alasan_penolakan;
            $booking->saran_tanggal = $request->saran_tanggal;
            $booking->saran_jam = $request->saran_jam;
            $booking->save();
            return response()->json(['status' => 'success', 'message' => 'Tawaran negosiasi berhasil dikirim ke member.']);
        }
    }

    // 3. Scan QR Code Absensi PT (Mengurangi sisa sesi paket 12 -> 11)
    public function scanQrPt(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:booking_pts,booking_id'
        ]);

        $booking = BookingPt::with(['memberPaket.member.lokasi', 'instruktur'])->find($request->booking_id);

        if ($booking->status === 'Hadir') {
            return response()->json(['status' => 'error', 'message' => 'QR Code sudah digunakan (Sesi Selesai).'], 400);
        }

        $memberPaket = $booking->memberPaket;

        if ($memberPaket->sisa_sesi <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Sisa sesi paket PT member sudah habis!'], 400);
        }

        // A. Ubah status booking jadi Hadir
        $booking->status = 'Hadir';
        $booking->waktu_scan_qr = Carbon::now();
        $booking->save();

        // B. Kurangi sisa sesi member (-1)
        // Hitung total pemakaian sebelum dikurangi, untuk tau ini sesi ke-berapa
        $paketUtuh = \App\Models\Paket::find($memberPaket->paket_id)->jumlah_sesi ?? 12; 
        $sesiTerpakaiSaatIni = $paketUtuh - $memberPaket->sisa_sesi + 1;

        $memberPaket->sisa_sesi = $memberPaket->sisa_sesi - 1;
        
        if ($memberPaket->sisa_sesi <= 0) {
            $memberPaket->status = 'Habis';
            $memberPaket->sisa_sesi = 0; // proteksi
        }
        $memberPaket->save();

        // C. Tulis ke Riwayat Penggunaan
        \App\Models\RiwayatPenggunaanPt::create([
            'member_paket_id' => $memberPaket->member_paket_id,
            'booking_id' => $booking->booking_id,
            'waktu_penggunaan' => Carbon::now(),
            'urutan_sesi' => $sesiTerpakaiSaatIni,
            'keterangan' => "Sesi ke-{$sesiTerpakaiSaatIni} telah diselesaikan bersama Coach " . ($booking->instruktur->nama ?? 'Instruktur'),
            // cabang_lokasi opsional, jika bisa diambil dari member_paket_id / member
            'cabang_lokasi' => $memberPaket->member->lokasi->nama_cabang ?? 'Cabang Gym Anda' 
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi PT berhasil! Sisa sesi member sekarang: ' . $memberPaket->sisa_sesi,
            'sisa_sesi' => $memberPaket->sisa_sesi
        ]);
    }

    // Menampilkan daftar jadwal luang (ketersediaan) milik instruktur
    public function getKetersediaanPt($instruktur_id)
    {
        $data = \App\Models\KetersediaanInstruktur::where('instruktur_id', $instruktur_id)
                ->orderBy('tanggal', 'desc') // Urutkan dari yang terbaru
                ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    // 4. Riwayat Melatih PT (Semua Klien) - Option A
    public function getRiwayatMelatihUmum($instruktur_id)
    {
        $riwayat = \App\Models\RiwayatPenggunaanPt::with(['booking.instruktur', 'memberPaket.member.lokasi', 'memberPaket.paket'])
            ->whereHas('booking', function ($query) use ($instruktur_id) {
                $query->where('instruktur_id', $instruktur_id);
            })
            ->orderBy('waktu_penggunaan', 'desc')
            ->get();
        return response()->json(['status' => 'success', 'data' => $riwayat], 200);
    }

    // 5. Riwayat Melatih PT per Klien - Option B
    // Kita berikan daftar Klien yang aktif/pernah dilatih yang ada relasi dengan instruktur_id ini, lalu beserta riwayat history per klien
    public function getDaftarKlienDenganRiwayat($instruktur_id)
    {
        $klien = \App\Models\MemberPaketPt::with([
            'member',
            'paket',
            'bookingPts' => function($query) {
                // Booking mendatang
                $query->whereIn('status', ['Approved', 'Pending', 'Negotiating'])
                      ->orderBy('tanggal_sesi', 'asc');
            },
            'riwayatPenggunaan' => function($query) use ($instruktur_id) {
                // History riwayat terpotong khusus oleh instruktur ini
                $query->whereHas('booking', function($b) use ($instruktur_id) {
                    $b->where('instruktur_id', $instruktur_id);
                })->orderBy('waktu_penggunaan', 'desc');
            }
        ])
        ->where('instruktur_id', $instruktur_id)
        ->get();

        return response()->json(['status' => 'success', 'data' => $klien], 200);
    }
    
    public function getRatingSaya($instruktur_id)
        {
            // =========================
            // CEK INSTRUKTUR
            // =========================

            $instruktur = Instruktur::find($instruktur_id);

            if (!$instruktur) {

                return response()->json([
                    'status' => 'error',
                    'message' => 'Instruktur tidak ditemukan'
                ], 404);
            }

            // =========================
            // AMBIL RATING
            // =========================

            $ratings = InstrukturRating::where(
                'instruktur_id',
                $instruktur_id
            )
            ->latest()
            ->get();

            // =========================
            // HITUNG RATA-RATA
            // =========================

            $avgRating = round(
                $ratings->avg('rating'),
                1
            );

            if (!$avgRating) {
                $avgRating = 0;
            }

            // =========================
            // RESPONSE
            // =========================

            return response()->json([
                'status' => 'success',
                'rating' => $avgRating,
                'data' => $ratings
            ]);
        }
    
}