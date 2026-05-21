<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    // 1. Tampilkan Halaman Data Member (Read / Index)
    public function index(Request $request)
    {
        $query = Member::with(['paketPts.instruktur', 'lokasi'])->orderBy('created_at', 'desc');

        // Filter by Search (Nama, Email, HP)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        // Filter by Bulan Gabung
        if ($request->filled('bulan') && $request->bulan != '') {
            $query->whereMonth('tanggal_mulai_member', $request->bulan)
                  ->orWhereMonth('created_at', $request->bulan);
        }

        // Filter by Membership Status
        if ($request->filled('status_membership') && $request->status_membership != '') {
            $status = $request->status_membership;
            if ($status == 'Aktif') {
                $query->where('status_membership', 'Aktif')->whereDate('tanggal_berakhir_member', '>=', now());
            } elseif ($status == 'Tidak Aktif') {
                $query->where(function($q) {
                    $q->where('status_membership', '!=', 'Aktif')
                      ->orWhereNull('tanggal_berakhir_member')
                      ->orWhereDate('tanggal_berakhir_member', '<', now());
                });
            } elseif (strpos($status, 'paket_') === 0) {
                $paketId = str_replace('paket_', '', $status);
                $query->whereHas('pembayarans', function($q) use ($paketId) {
                    $q->where('status', 'settlement')
                      ->whereHas('pemesanan', function($q2) use ($paketId) {
                          $q2->where('paket_id', $paketId);
                      });
                });
            }
        }

        // Filter by Lokasi (Cabang)
        if ($request->filled('lokasi_id') && $request->lokasi_id != '') {
            $query->where('lokasi_id', $request->lokasi_id);
        }

        $members = $query->get();
        $lokasis = Lokasi::all();
        $pakets = \App\Models\Paket::all();

        return view('member.index', compact('members', 'lokasis', 'pakets'));
    }

    public function exportExcel(Request $request)
    {
        $members = Member::with(['paketPts.instruktur'])->orderBy('created_at', 'desc')->get();

        $filename = "data_member_" . date('Y-m-d_H-i-s') . ".csv";
        $handle = fopen('php://output', 'w');
        
        $headers = array(
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
        );

        ob_start();
        fputcsv($handle, ['No', 'Nama', 'Konta (Email/HP)', 'Status Membership', 'Paket Gym Umum', 'Paket Personal Trainer']);

        foreach ($members as $index => $member) {
            $status_gym = ($member->status_membership == 'Aktif' && $member->tanggal_berakhir_member) ? 'Aktif s/d ' . \Carbon\Carbon::parse($member->tanggal_berakhir_member)->format('d M Y') : 'Tidak Aktif';
            
            $pt_info = '-';
            if ($member->paketPts && $member->paketPts->count() > 0) {
                $pt = $member->paketPts->first();
                $coach = $pt->instruktur ? $pt->instruktur->nama : 'Belum Ada';
                $pt_info = "Sisa " . $pt->sisa_sesi . " Sesi (Coach: " . $coach . ")";
            }

            fputcsv($handle, [
                $index + 1,
                $member->nama,
                $member->email . ' / ' . ($member->no_hp ?? '-'),
                $member->status_membership,
                $status_gym,
                $pt_info
            ]);
        }
        fclose($handle);
        return \Response::make(ob_get_clean(), 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $members = Member::with(['paketPts.instruktur'])->orderBy('created_at', 'desc')->get();
        $pdf = \Pdf::loadView('member.pdf', compact('members'));
        return $pdf->download('data_member_'.date('Ymd_His').'.pdf');
    }

    // 2. Tampilkan Detail Member (Show)
    public function show($id)
    {
        $member = Member::with(['paketPts.instruktur'])->findOrFail($id);
        return view('member.show', compact('member'));
    }

    // 3. Tampilkan Form Edit Member (Edit)
    public function edit($id)
    {
        $member = Member::findOrFail($id);
        return view('member.edit', compact('member'));
    }

    // 4. Proses Update Data Member (Update)
    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:members,email,' . $member->member_id . ',member_id',
            'no_hp' => 'nullable|string|max:15',
            'status_membership' => 'required|in:Aktif,Tidak Aktif',
            'tanggal_mulai_member' => 'nullable|date',
            'tanggal_berakhir_member' => 'nullable|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Persiapkan data yang akan diupdate
        $data = [
            'nama' => $request->nama,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'status_membership' => $request->status_membership,
            'tanggal_mulai_member' => $request->tanggal_mulai_member,
            'tanggal_berakhir_member' => $request->tanggal_berakhir_member,
        ];

        // Jika form password diisi, maka update password (jika kosong, biarkan password lama)
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('foto')) {
            if ($member->foto && \Storage::disk('public')->exists($member->foto)) {
                \Storage::disk('public')->delete($member->foto);
            }
            $data['foto'] = $request->file('foto')->store('members', 'public');
        }

        $member->update($data);

        return redirect()->route('member.index')->with('success', 'Data member berhasil diperbarui!');
    }

    // 5. Proses Hapus Data Member (Destroy)
    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $member->delete();

        return redirect()->route('member.index')->with('success', 'Data member berhasil dihapus!');
    }
}