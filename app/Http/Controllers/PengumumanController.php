<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PengumumanController extends Controller
{
    public function index()
    {
        $pengumumans = Pengumuman::orderBy('tanggal_post', 'desc')->paginate(15)->withQueryString();
        return view('pengumuman.index', compact('pengumumans'));
    }

    public function create()
    {
        return view('pengumuman.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->all();
        $data['admin_id'] = Auth::guard('admin')->id();
        $data['tanggal_post'] = now();
        $data['tampil_web'] = $request->has('tampil_web');
        $data['tampil_member'] = $request->has('tampil_member');
        $data['tampil_instruktur'] = $request->has('tampil_instruktur');

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('pengumuman', 'public');
        }

        Pengumuman::create($data);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil diposting!');
    }

    // Tampilkan Form Edit
    public function edit($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        return view('pengumuman.edit', compact('pengumuman'));
    }

    // Proses Update Data
    public function update(Request $request, $id)
    {
        $pengumuman = Pengumuman::findOrFail($id);

        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->except('foto');
        $data['tampil_web'] = $request->has('tampil_web');
        $data['tampil_member'] = $request->has('tampil_member');
        $data['tampil_instruktur'] = $request->has('tampil_instruktur');

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada foto baru yang diunggah
            if ($pengumuman->foto) {
                Storage::disk('public')->delete($pengumuman->foto);
            }
            // Simpan foto baru
            $data['foto'] = $request->file('foto')->store('pengumuman', 'public');
        }

        $pengumuman->update($data);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        if ($pengumuman->foto) {
            Storage::disk('public')->delete($pengumuman->foto);
        }
        $pengumuman->delete();

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman dihapus!');
    }
}