<?php

namespace App\Http\Controllers;

use App\Models\Instruktur;
use App\Models\Lokasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class InstrukturController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil semua data lokasi untuk mengisi dropdown filter
        $lokasis = Lokasi::all();

        // 2. Siapkan query dasar untuk Instruktur (Eager Loading lokasi dan hitung member aktif)
        $query = Instruktur::with('lokasi')->withCount(['memberPaketPts' => function ($query) {
            $query->where('status', 'Aktif')->whereHas('member');
        }]);

        // 3. Cek apakah admin memilih filter lokasi tertentu
        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
        }

        // 4. Filter by Search (Nama atau Username)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // 5. Eksekusi query
        $instrukturs = $query->paginate(15)->withQueryString();

        return view('instruktur.index', compact('instrukturs', 'lokasis'));
    }

    public function create()
    {
        // Panggil semua data cabang untuk mengisi dropdown
        $lokasis = Lokasi::all();
        return view('instruktur.create', compact('lokasis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:instrukturs,username',
            'password' => 'required|string|min:6',
            'spesialisasi' => 'nullable|string|max:255',
            'lokasi_id' => 'required|exists:lokasis,lokasi_id',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validasi foto
        ]);

        $data = [
            'nama' => $request->nama,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'spesialisasi' => $request->spesialisasi,
            'lokasi_id' => $request->lokasi_id,
        ];

        // Jika admin mengupload foto
        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('instruktur', 'public');
        }

        Instruktur::create($data);

        return redirect()->route('instruktur.index')->with('success', 'Instruktur berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $instruktur = Instruktur::findOrFail($id);
        $lokasis = Lokasi::all();
        return view('instruktur.edit', compact('instruktur', 'lokasis'));
    }

    public function update(Request $request, $id)
    {
        $instruktur = Instruktur::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:instrukturs,username,'.$id.',instruktur_id',
            'password' => 'nullable|string|min:6', 
            'spesialisasi' => 'nullable|string|max:255',
            'lokasi_id' => 'required|exists:lokasis,lokasi_id',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validasi foto
        ]);

        $data = [
            'nama' => $request->nama,
            'username' => $request->username,
            'spesialisasi' => $request->spesialisasi,
            'lokasi_id' => $request->lokasi_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Jika admin mengganti foto
        if ($request->hasFile('foto')) {
            // Hapus foto lama dari storage jika ada
            if ($instruktur->foto && Storage::disk('public')->exists($instruktur->foto)) {
                Storage::disk('public')->delete($instruktur->foto);
            }
            // Simpan foto baru
            $data['foto'] = $request->file('foto')->store('instruktur', 'public');
        }

        $instruktur->update($data);

        return redirect()->route('instruktur.index')->with('success', 'Data Instruktur berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $instruktur = Instruktur::findOrFail($id);
        $instruktur->delete();
        
        return redirect()->route('instruktur.index')->with('success', 'Instruktur berhasil dihapus!');
    }

    public function clients($id)
    {
        $instruktur = Instruktur::findOrFail($id);
        
        // Ambil member yang memiliki paket PT aktif dengan instruktur ini
        $clients = \App\Models\Member::whereHas('paketPts', function($q) use ($id) {
            $q->where('instruktur_id', $id)->where('status', 'Aktif');
        })->with(['paketPts' => function($q) use ($id) {
            $q->where('instruktur_id', $id);
        }])->paginate(15)->withQueryString();
        
        return view('instruktur.clients', compact('instruktur', 'clients'));
    }
}