<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Midtrans\Config;
use Midtrans\Snap;

// --- SEMUA MODEL YANG DIBUTUHKAN ---
use App\Models\Member;
use App\Models\Paket;
use App\Models\Instruktur;
use App\Models\PemesananPaket;
use App\Models\Pembayaran;
use App\Models\JadwalLatihan;
use App\Models\BookingAbsensi;
use App\Models\Presensi;
use App\Models\MemberPaketPt;
use App\Models\KetersediaanInstruktur;
use App\Models\BookingPt;
use App\Models\Lokasi; // <--- Pastikan ini ada
use App\Models\KunjunganGym;
use App\Models\InstrukturRating;

class MemberApiController extends Controller
{
    // =========================================================================
    // BAGIAN 1: AUTENTIKASI & PROFIL (POIN 2, 3, 4, 11, 12)
    // =========================================================================

    public function register(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:members,email',
            'no_hp' => 'required|string|max:15',
            'password' => 'required|string|min:6',
            'lokasi_id' => 'nullable',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('members', 'public');
        }

        $member = Member::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'password' => Hash::make($request->password),
            'status_membership' => 'Tidak Aktif',
            'lokasi_id' => $request->lokasi_id,
            'foto' => $fotoPath
        ]);

        return response()->json(['status' => 'success', 'message' => 'Registrasi berhasil!', 'data' => $member], 201);
    }

    public function getLokasis()
    {
        $lokasis = Lokasi::all();
        
        // Terjemahkan nama kolom database (nama_cabang) ke format Android (nama_lokasi)
        $formattedLokasi = $lokasis->map(function ($cabang) {
            return [
                'lokasi_id' => $cabang->lokasi_id, 
                'nama_lokasi' => $cabang->nama_cabang // <--- INI KUNCINYA
            ];
        });
        
        return response()->json($formattedLokasi, 200);
    }

    public function login(Request $request)
    {
        $member = Member::where('email', $request->email)->first();
        if (!$member || !Hash::check($request->password, $member->password)) {
            return response()->json(['status' => 'error', 'message' => 'Email atau Password salah'], 401);
        }

        if ($member->foto) {
            $member->foto_url = asset('storage/' . $member->foto);
        } else {
            $member->foto_url = null;
        }

        return response()->json(['status' => 'success', 'message' => 'Login berhasil', 'data' => $member], 200);
    }

    public function getProfile($id)
    {
        $member = Member::with(['paketPts.instruktur'])->find($id);
        if (!$member) return response()->json(['status' => 'error', 'message' => 'Member tidak ditemukan'], 404);
        
        // Cari paket terakhir yang dibeli dan disetujui (opsional legacy)
        $latestPemesanan = \App\Models\PemesananPaket::where('member_id', $id)
                            ->where('status_persetujuan', 'Disetujui')
                            ->orderBy('tanggal_pesan', 'desc')
                            ->with('paket')
                            ->first();

        // Tambahkan properti baru secara dinamis untuk dikirim ke Android
        $member->nama_paket_aktif = $latestPemesanan && $latestPemesanan->paket ? $latestPemesanan->paket->nama_paket : null;

        // Tambahan fitur: Info Masa Aktif dan PT
        $member->masa_aktif_gym = ($member->status_membership == 'Aktif' && $member->tanggal_berakhir_member) 
            ? 'Aktif s/d ' . \Carbon\Carbon::parse($member->tanggal_berakhir_member)->format('d M Y') 
            : 'Tidak Aktif';

        $pt_info = 'Tidak Ada / Habis';
        if ($member->paketPts && $member->paketPts->count() > 0) {
            $pt = $member->paketPts->first();
            $coach = $pt->instruktur ? $pt->instruktur->nama : 'Belum Ada';
            $pt_info = "Sisa " . $pt->sisa_sesi . " Sesi (Coach: " . $coach . ")";
        }
        $member->informasi_paket_pt = $pt_info;
        
        if ($member->foto) {
            $member->foto_url = asset('storage/' . $member->foto);
        } else {
            $member->foto_url = null;
        }

        return response()->json(['status' => 'success', 'data' => $member], 200);
    }

    public function getDataFisik($id)
    {
        $data = \App\Models\DataFisikMember::where('member_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function simpanDataFisik(Request $request, $memberId)
    {
        $request->validate([
            'tinggi_badan' => 'required|numeric',
            'berat_badan' => 'required|numeric',
            'umur' => 'required|integer',
            'massa_otot' => 'nullable|numeric',
        ]);

        $data = \App\Models\DataFisikMember::create([
            'member_id' => $memberId,
            'tinggi_badan' => $request->tinggi_badan,
            'berat_badan' => $request->berat_badan,
            'umur' => $request->umur,
            'massa_otot' => $request->massa_otot
        ]);

        return response()->json(['status' => 'success', 'message' => 'Data fisik berhasil disimpan', 'data' => $data], 201);
    }

    public function getPengumuman()
    {
        $data = \App\Models\Pengumuman::where('tampil_member', true)
            ->orderBy('tanggal_post', 'desc')
            ->get();
        return response()->json(['status' => 'success', 'data' => $data], 200);
    }

    public function updateProfile(Request $request, $id)
    {
        // =========================
        // CARI MEMBER
        // =========================

        $member = Member::find($id);

        // jika member tidak ditemukan
        if (!$member) {

            return response()->json([
                'status' => 'error',
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        // =========================
        // SIMPAN LOKASI LAMA
        // =========================

        $lokasiLama = $member->lokasi_id;

        // =========================
        // VALIDASI
        // =========================

        $request->validate([
            'nama'       => 'required|string|max:255',
            'no_hp'      => 'required|string|max:15',
            'email'      => 'required|string|email|unique:members,email,' . $id . ',member_id',
            'password'   => 'nullable|string|min:6',
            'lokasi_id'  => 'nullable',
            'foto'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // =========================
        // UPDATE DATA MEMBER
        // =========================

        $member->nama      = $request->nama;
        $member->email     = $request->email;
        $member->no_hp     = $request->no_hp;
        $member->lokasi_id = $request->lokasi_id;

        // =========================
        // UPDATE PASSWORD
        // =========================

        if ($request->filled('password')) {

            $member->password =
                Hash::make($request->password);
        }

        // =========================
        // UPDATE FOTO
        // =========================

        if ($request->hasFile('foto')) {

            // hapus foto lama
            if (
                $member->foto &&
                \Storage::disk('public')->exists($member->foto)
            ) {

                \Storage::disk('public')
                    ->delete($member->foto);
            }

            // upload foto baru
            $member->foto = $request
                ->file('foto')
                ->store('members', 'public');
        }

        // =========================
        // SIMPAN MEMBER
        // =========================

        $member->save();

        // =========================
        // CEK PERUBAHAN CABANG
        // =========================

        if ($lokasiLama != $request->lokasi_id) {

            // =========================
            // CARI PAKET PT AKTIF TERBARU
            // =========================

            $paketPtAktif = MemberPaketPt::where(
                'member_id',
                $member->member_id
            )
            ->where('sisa_sesi', '>', 0)
            ->latest('member_paket_id')
            ->first();

            // =========================
            // RESET COACH
            // =========================

            if ($paketPtAktif) {

                $paketPtAktif->instruktur_id = null;

                $paketPtAktif->save();
            }
        }

        // =========================
        // REFRESH DATA MEMBER
        // =========================

        $member->refresh();

        // =========================
        // FOTO URL
        // =========================

        if ($member->foto) {

            $member->foto_url =
                asset('storage/' . $member->foto);

        } else {

            $member->foto_url = null;
        }

        // =========================
        // NAMA CABANG
        // =========================

        $lokasi = Lokasi::find(
            $member->lokasi_id
        );

        $member->nama_lokasi = $lokasi
            ? $lokasi->nama_cabang
            : null;

        // =========================
        // RESPONSE
        // =========================

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data'    => $member
        ], 200);
    }


    // =========================================================================
    // BAGIAN 2: MASTER DATA & TRANSAKSI (POIN 5 & 6)
    // =========================================================================

    public function getPakets()
    {
        $pakets = Paket::all()->map(function($paket) {
            $diskonAktif = $paket->harga_diskon && $paket->tanggal_akhir_diskon
                && Carbon::now()->startOfDay()->lte(Carbon::parse($paket->tanggal_akhir_diskon)->startOfDay());
            return [
                'paket_id'             => $paket->paket_id,
                'nama_paket'           => $paket->nama_paket,
                'jenis'                => $paket->jenis,
                'harga'                => $paket->harga,
                'durasi'               => $paket->durasi,
                'harga_diskon'         => $diskonAktif ? $paket->harga_diskon : null,
                'tanggal_akhir_diskon' => $diskonAktif ? $paket->tanggal_akhir_diskon : null,
                'is_diskon_aktif'      => $diskonAktif,
            ];
        });
        return response()->json(['status' => 'success', 'data' => $pakets], 200);
    }

    public function getDaftarInstruktur()
    {
        // Poin 6: Menampilkan foto, nama, spesialisasi
        $instruktur = Instruktur::all();
        return response()->json(['status' => 'success', 'data' => $instruktur], 200);
    }

    public function beliPaket(Request $request)
    {
        $request->validate(['member_id' => 'required', 'paket_id' => 'required']);
        $member = Member::find($request->member_id);
        $paket = Paket::find($request->paket_id);

        if (!$member || !$paket) return response()->json(['status' => 'error', 'message' => 'Data tidak valid'], 404);

        $pemesanan = PemesananPaket::create([
            'member_id' => $member->member_id,
            'paket_id' => $paket->paket_id,
            'status_persetujuan' => 'Pending',
            'tanggal_pesan' => now()->toDateString(),
        ]);

        $orderId = 'GYM-' . $pemesanan->getKey() . '-' . time();

        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        $diskonAktif = $paket->harga_diskon && $paket->tanggal_akhir_diskon
            && Carbon::now()->startOfDay()->lte(Carbon::parse($paket->tanggal_akhir_diskon)->startOfDay());
        $hargaBayar = $diskonAktif ? $paket->harga_diskon : $paket->harga;

        $params = [
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => (int) $hargaBayar],
            'customer_details'    => ['first_name' => $member->nama, 'email' => $member->email, 'phone' => $member->no_hp]
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['status' => 'success', 'snap_token' => $snapToken, 'order_id' => $orderId], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function midtransCallback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed == $request->signature_key) {
            if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
                
                $orderParts = explode('-', $request->order_id);
                $pemesananId = $orderParts[1];
                $pemesanan = PemesananPaket::with('paket')->find($pemesananId);
                
                if ($pemesanan && $pemesanan->status_persetujuan == 'Pending') {
                    
                    // 1. Ubah status pemesanan
                    $pemesanan->status_persetujuan = 'Disetujui';
                    $pemesanan->save();

                    // 2. Catat ke Riwayat Pembayaran (Poin 10)
                    Pembayaran::create([
                        'pemesanan_id' => $pemesanan->pemesanan_id,
                        'member_id' => $pemesanan->member_id,
                        'order_id' => $request->order_id,
                        'metode' => $request->payment_type,
                        'jumlah' => $request->gross_amount,
                        'status' => 'settlement',
                        'tanggal_bayar' => now(),
                    ]);

                    // 3. LOGIKA KUNCI: Bedakan Paket Gym Umum vs Personal Trainer
                    $paket = $pemesanan->paket;
                    $member = Member::find($pemesanan->member_id); // <--- Tarik data member di sini

                    if ($paket->jenis == 'Personal Training' || str_contains(strtolower($paket->nama_paket), 'pt')) {
                        // A. Jika beli PT -> Tambah kuota sesi di tabel khusus PT
                        MemberPaketPt::create([
                            'member_id' => $pemesanan->member_id,
                            'paket_id' => $paket->paket_id,
                            'sisa_sesi' => $paket->jumlah_sesi ?? 12, 
                            'expired_date' => Carbon::now()->addDays($paket->durasi ?? 30)->toDateString(),
                            'status' => 'Aktif'
                        ]);

                        // B. PERBAIKAN: Aktifkan juga status membership utamanya agar Dashboard Hijau!
                        $member->status_membership = 'Aktif';
                        $member->tanggal_mulai_member = Carbon::now()->toDateString();
                        // Opsional: Set masa aktif member sesuai durasi paket PT
                        $member->tanggal_berakhir_member = Carbon::now()->addDays($paket->durasi ?? 30)->toDateString();
                        $member->save();

                    } else {
                        // Jika beli Gym Umum -> Aktifkan Member & Set Tanggal Habis
                        $member->status_membership = 'Aktif';
                        $member->tanggal_mulai_member = Carbon::now()->toDateString();
                        $member->tanggal_berakhir_member = Carbon::now()->addDays($paket->durasi ?? 30)->toDateString();
                        $member->save();
                    }
                }
            }
        }
        return response()->json(['message' => 'Callback diproses']);
    }


    // =========================================================================
    // BAGIAN 3: JADWAL & BOOKING GYM UMUM (KELAS)
    // =========================================================================

    public function getJadwalGymUmum()
    {
        $jadwal = JadwalLatihan::with('instruktur')->get();
        return response()->json(['status' => 'success', 'data' => $jadwal], 200);
    }

    public function bookingGymUmum(Request $request)
    {
        $request->validate(['member_id' => 'required', 'jadwal_id' => 'required']);
        $jadwal = JadwalLatihan::findOrFail($request->jadwal_id);

        $jumlahBooking = BookingAbsensi::where('jadwal_id', $request->jadwal_id)->where('status_booking', 'Booked')->count();
        if ($jumlahBooking >= $jadwal->kuota) {
            return response()->json(['status' => 'error', 'message' => 'Kuota penuh'], 400);
        }

        $booking = BookingAbsensi::create([
            'member_id' => $request->member_id,
            'jadwal_id' => $request->jadwal_id,
            'instruktur_id' => $jadwal->instruktur_id,
            'tanggal' => now()->toDateString(),
            'status_booking' => 'Booked',
            'status_hadir' => 'Belum Absen'
        ]);

        return response()->json(['status' => 'success', 'message' => 'Berhasil booking kelas', 'data' => $booking], 201);
    }


    // =========================================================================
    // BAGIAN 4: JADWAL & BOOKING PERSONAL TRAINER (POIN 7)
    // =========================================================================

    public function getPaketPtAktif($member_id)
    {
        // Tambahkan with('instruktur')
        $paket = MemberPaketPt::with('instruktur')
            ->where('member_id', $member_id)
            ->where('status', 'Aktif')->where('sisa_sesi', '>', 0)
            ->where('expired_date', '>=', Carbon::now()->toDateString())->get();
        return response()->json(['status' => 'success', 'data' => $paket], 200);
    }

public function getCoachCabang($member_id)
{
    // =========================
    // AMBIL MEMBER
    // =========================

    $member = Member::find($member_id);

    if (!$member) {

        return response()->json([
            'status' => 'error',
            'message' => 'Member tidak ditemukan'
        ], 404);
    }

    // =========================
    // AMBIL COACH SESUAI CABANG
    // =========================

    $coaches = Instruktur::where(
        'lokasi_id',
        $member->lokasi_id
    )->get();

    // =========================
    // TAMBAHKAN RATING
    // =========================

    foreach ($coaches as $coach) {

        $coach->rating = round(

            InstrukturRating::where(
                'instruktur_id',
                $coach->instruktur_id
            )->avg('rating') ?? 0,

            1
        );
    }

    // =========================
    // RESPONSE
    // =========================

    return response()->json([
        'status' => 'success',
        'data' => $coaches
    ], 200);
}

    public function pilihCoachPt(Request $request)
    {
        $request->validate([
            'member_paket_id' => 'required',
            'instruktur_id' => 'required'
        ]);

        $paket = MemberPaketPt::find(
            $request->member_paket_id
        );

        if (!$paket) {

            return response()->json([
                'status' => 'error',
                'message' => 'Paket PT tidak ditemukan'
            ], 404);
        }

        $member = Member::find($paket->member_id);

        $instruktur = Instruktur::find(
            $request->instruktur_id
        );

        if (!$instruktur) {

            return response()->json([
                'status' => 'error',
                'message' => 'Coach tidak ditemukan'
            ], 404);
        }

        // validasi cabang
        if (
            $instruktur->lokasi_id !=
            $member->lokasi_id
        ) {

            return response()->json([
                'status' => 'error',
                'message' =>
                    'Coach tidak sesuai cabang member'
            ], 400);
        }

        // =========================
        // LOCK COACH
        // =========================

        // jika sudah pernah pilih coach
        // dan sesi masih berjalan
        if (
            $paket->instruktur_id != null &&
            $paket->sisa_sesi > 0
        ) {

            return response()->json([
                'status' => 'error',
                'message' =>
                    'Coach terkunci sampai sesi selesai'
            ], 400);
        }

        // =========================
        // SIMPAN COACH
        // =========================

        $paket->instruktur_id =
            $request->instruktur_id;

        $paket->save();

        return response()->json([
            'status' => 'success',
            'message' =>
                'Coach berhasil dipilih'
        ], 200);
    }

    public function getInstrukturPtTersedia($member_id) 
    {
        // 1. Cari paket PT aktif milik member ini
        $paketPt = MemberPaketPt::where('member_id', $member_id)
                    ->where('status', 'Aktif')
                    ->where('sisa_sesi', '>', 0)
                    ->first();

        // 2. Jika belum punya paket atau belum memilih coach, kembalikan kosong
        if (!$paketPt || !$paketPt->instruktur_id) {
            return response()->json(['status' => 'success', 'data' => []], 200);
        }

        // 3. Ambil jadwal HANYA untuk instruktur yang SUDAH DIPILIH di paket tersebut
        $tersedia = KetersediaanInstruktur::with('instruktur')
            ->where('instruktur_id', $paketPt->instruktur_id) // <--- FILTER KUNCI
            ->where('tanggal', '>=', Carbon::now()->toDateString())
            ->where('is_booked', 0)
            ->orderBy('tanggal', 'asc')->orderBy('jam_mulai', 'asc')->get();

        return response()->json(['status' => 'success', 'data' => $tersedia], 200);
    }

    public function bookingPt(Request $request)
    {
        // =========================
        // VALIDASI
        // =========================

        $request->validate([
            'member_paket_id' => 'required',
            'tanggal_sesi' => 'required|date',
            'jam_sesi' => 'required'
        ]);

        // =========================
        // AMBIL PAKET PT
        // =========================

        $paket = MemberPaketPt::find(
            $request->member_paket_id
        );

        // jika paket tidak ditemukan
        if (!$paket) {

            return response()->json([
                'status' => 'error',
                'message' => 'Paket PT tidak ditemukan'
            ], 404);
        }

        // =========================
        // WAJIB PILIH COACH
        // =========================

        if (!$paket->instruktur_id) {

            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum memilih Coach PT'
            ], 400);
        }

        // =========================
        // CEK SISA SESI
        // =========================

        if ($paket->sisa_sesi <= 0) {

            return response()->json([
                'status' => 'error',
                'message' => 'Sisa sesi habis'
            ], 400);
        }

        // =========================
        // BUAT BOOKING
        // =========================

        $booking = BookingPt::create([

            'member_paket_id' =>
                $request->member_paket_id,

            'instruktur_id' =>
                $paket->instruktur_id,

            'tanggal_sesi' =>
                $request->tanggal_sesi,

            'jam_sesi' =>
                $request->jam_sesi,

            'status' => 'Pending'
        ]);

        // =========================
        // KURANGI SESI
        // =========================

        $paket->sisa_sesi -= 1;

        // =========================
        // JIKA SESI HABIS
        // =========================

        if ($paket->sisa_sesi <= 0) {

            // unlock coach
            $paket->instruktur_id = null;

            // update status paket
            $paket->status = 'Selesai';
        }

        // =========================
        // SIMPAN PAKET
        // =========================

        $paket->save();

        // =========================
        // RESPONSE
        // =========================

        return response()->json([

            'status' => 'success',

            'message' =>
                'Berhasil mengajukan jadwal PT',

            'sisa_sesi' =>
                $paket->sisa_sesi,

            'data' => $booking

        ], 200);
    }

    public function tanggapanNegosiasiPt(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:booking_pts,booking_id',
            'tindakan' => 'required|in:terima,reschedule'
        ]);
        
        $booking = BookingPt::find($request->booking_id);
        if ($booking->status !== 'Negotiating' && $booking->status !== 'Approved') {
            return response()->json(['status' => 'error', 'message' => 'Status tidak valid untuk proses ini.'], 400);
        }

        if ($request->tindakan === 'terima') {
            $booking->tanggal_sesi = $booking->saran_tanggal;
            $booking->jam_sesi = $booking->saran_jam;
            $booking->status = 'Approved';
            $booking->save();
            return response()->json(['status' => 'success', 'message' => 'Jadwal saran disetujui.']);
        } else {
            // reschedule (mengajukan ulang jadwal baru)
            $request->validate([
                'tanggal_sesi_baru' => 'required|date',
                'jam_sesi_baru' => 'required'
            ]);
            $booking->tanggal_sesi = $request->tanggal_sesi_baru;
            $booking->jam_sesi = $request->jam_sesi_baru;
            $booking->status = 'Pending';
            // bersihkan alasan dan saran
            $booking->alasan_penolakan = null;
            $booking->saran_tanggal = null;
            $booking->saran_jam = null;
            $booking->save();
            return response()->json(['status' => 'success', 'message' => 'Jadwal baru berhasil diajukan.']);
        }
    }


    // =========================================================================
    // BAGIAN 5: PUSAT RIWAYAT MEMBER (POIN 10)
    // =========================================================================

    public function getRiwayatPembayaran($member_id)
    {
        $riwayat = Pembayaran::select('pembayarans.*', 'pakets.nama_paket')
            ->join('pemesanan_pakets', 'pembayarans.pemesanan_id', '=', 'pemesanan_pakets.pemesanan_id')
            ->join('pakets', 'pemesanan_pakets.paket_id', '=', 'pakets.paket_id')
            ->where('pembayarans.member_id', $member_id)
            ->orderBy('pembayarans.tanggal_bayar', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $riwayat], 200);
    }

    public function getRiwayatLatihan($member_id)
    {
        // Poin 10: Riwayat Kehadiran Latihan Biasa (Dari tabel KunjunganGym)
        $riwayat = KunjunganGym::where('member_id', $member_id)->orderBy('tanggal', 'desc')->orderBy('waktu_masuk', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $riwayat], 200);
    }

    public function getRiwayatBookingPt($member_id)
    {
        // Poin 10: Riwayat Booking Instruktur PT
        $riwayat = BookingPt::with(['instruktur'])
            ->whereHas('memberPaket', function($query) use ($member_id) {
                $query->where('member_id', $member_id);
            })->orderBy('created_at', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $riwayat], 200);
    }

    public function getRiwayatSesiPt($member_id)
    {
        // Poin Revisi Dosen: Riwayat pemotongan sesi PT secara detail
        $riwayat = \App\Models\RiwayatPenggunaanPt::with(['booking.instruktur', 'memberPaket.paket'])
            ->whereHas('memberPaket', function($query) use ($member_id) {
                $query->where('member_id', $member_id);
            })
            ->orderBy('waktu_penggunaan', 'desc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $riwayat], 200);
    }

    // =========================================================================
    // BAGIAN 6: PRESENSI MANDIRI (CHECK-IN GEDUNG)
    // =========================================================================

    public function getStatusPresensi($member_id)
    {
        // Mengecek apakah hari ini member sudah check-in dan belum check-out
        $kunjungan = KunjunganGym::where('member_id', $member_id)
            ->where('tanggal', now()->toDateString())
            ->where('status_kunjungan', 'Masuk')
            ->first();

        return response()->json([
            'status' => 'success',
            'is_check_in' => $kunjungan ? true : false,
            'data' => $kunjungan
        ]);
    }

    public function checkIn(Request $request)
    {
        $request->validate(['member_id' => 'required', 'lokasi_id' => 'required']);

        // --- 1. SATPAM VALIDASI MEMBERSHIP ---
        $member = Member::find($request->member_id);
        
        // Cek apakah statusnya tidak aktif, atau tanggal berakhirnya sudah lewat
        if (!$member || $member->status_membership !== 'Aktif' || $member->tanggal_berakhir_member < now()->toDateString()) {
            // Jika membership mati/habis, tolak Check-in!
            return response()->json([
                'status' => 'error', 
                'message' => 'Gagal: Membership Anda belum aktif atau sudah kedaluwarsa. Silakan beli paket terlebih dahulu.'
            ], 403);
        }
        // -------------------------------------

        // --- 2. CEK DOUBLE CHECK-IN ---
        $cekSudahMasuk = KunjunganGym::where('member_id', $request->member_id)
                            ->where('tanggal', now()->toDateString())
                            ->where('status_kunjungan', 'Masuk')
                            ->first();

        if ($cekSudahMasuk) {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah Check-in sebelumnya.'], 400);
        }

        // --- 3. CATAT CHECK-IN ---
        $kunjungan = KunjunganGym::create([
            'member_id' => $request->member_id,
            'lokasi_id' => $request->lokasi_id,
            'tanggal' => now()->toDateString(),
            'waktu_masuk' => now()->toTimeString(), // Sekarang sudah pakai jam WIB yang benar!
            'status_kunjungan' => 'Masuk'
        ]);

        return response()->json(['status' => 'success', 'message' => 'Berhasil Check-in! Selamat berlatih.', 'data' => $kunjungan], 201);
    }

    public function checkOut(Request $request)
    {
        // Cari data check-in hari ini yang statusnya masih 'Masuk'
        $kunjungan = KunjunganGym::where('member_id', $request->member_id)
            ->where('tanggal', now()->toDateString())
            ->where('status_kunjungan', 'Masuk')
            ->first();

        if ($kunjungan) {
            $kunjungan->waktu_keluar = now()->toTimeString();
            $kunjungan->status_kunjungan = 'Selesai';
            $kunjungan->save();
            
            return response()->json(['status' => 'success', 'message' => 'Berhasil Check-out! Hati-hati di jalan.']);
        }

        return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan / Anda belum Check-in'], 404);
    }

    public function rateInstruktur(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,member_id',
            'instruktur_id' => 'required|exists:instrukturs,instruktur_id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string'
        ]);

        $rating = \App\Models\InstrukturRating::create([
            'member_id' => $request->member_id,
            'instruktur_id' => $request->instruktur_id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Terima kasih atas penilaian Anda!',
            'data' => $rating
        ]);
    }

    public function pilihInstruktur(Request $request)
    {
        $request->validate([
            'member_id' => 'required',
            'instruktur_id' => 'required'
        ]);

        $member = Member::find($request->member_id);

        if (!$member) {
            return response()->json([
                'status' => 'error',
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        // Cari paket PT aktif
        $paketPt = MemberPaketPt::where('member_id', $member->member_id)
            ->where('status', 'Aktif')
            ->first();

        if (!$paketPt) {
            return response()->json([
                'status' => 'error',
                'message' => 'Paket PT aktif tidak ditemukan'
            ], 404);
        }

        // =========================
        // LOCK INSTRUKTUR
        // =========================

        if (
            $paketPt->instruktur_id != null &&
            $paketPt->sisa_sesi > 0
        ) {

            return response()->json([
                'status' => 'error',
                'message' => 'Instruktur tidak dapat diganti sebelum sesi selesai'
            ], 400);
        }

        // =========================
        // VALIDASI CABANG
        // =========================

        $instruktur = Instruktur::find($request->instruktur_id);

        if (!$instruktur) {
            return response()->json([
                'status' => 'error',
                'message' => 'Instruktur tidak ditemukan'
            ], 404);
        }

        // instruktur harus sesuai cabang member
        if ($instruktur->lokasi_id != $member->lokasi_id) {

            return response()->json([
                'status' => 'error',
                'message' => 'Instruktur tidak sesuai cabang member'
            ], 400);
        }

        // =========================
        // SIMPAN INSTRUKTUR
        // =========================

        $paketPt->instruktur_id = $request->instruktur_id;

        $paketPt->lokasi_id = $member->lokasi_id;

        $paketPt->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Instruktur berhasil dipilih'
        ], 200);
    }

        public function rateInstruktur(Request $request)
    {
        $request->validate([
            'member_id' => 'required',
            'instruktur_id' => 'required',
            'booking_id' => 'required',
            'rating' => 'required|min:1|max:5'
        ]);

        InstrukturRating::create([
            'member_id' => $request->member_id,
            'instruktur_id' => $request->instruktur_id,
            'booking_id' => $request->booking_id,
            'rating' => $request->rating
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rating berhasil dikirim'
        ]);
    }

}