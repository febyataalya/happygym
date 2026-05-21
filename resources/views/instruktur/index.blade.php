<x-app-layout>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-900">Daftar Instruktur</h2>
        <a href="{{ route('instruktur.create') }}" class="bg-[#e45151] hover:bg-red-700 text-white font-semibold py-2 px-6 rounded shadow-sm transition">
            + Tambah Instruktur
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <form action="{{ route('instruktur.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama instruktur..." class="border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 text-sm w-64">
            
            <label for="lokasi_id" class="text-sm font-bold text-gray-700">Cabang:</label>
            <select name="lokasi_id" id="lokasi_id" class="border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 text-sm min-w-[200px]">
                <option value="">-- Semua Cabang --</option>
                @foreach($lokasis as $l)
                    <option value="{{ $l->lokasi_id }}" {{ request('lokasi_id') == $l->lokasi_id ? 'selected' : '' }}>
                        {{ $l->nama_cabang }} ({{ $l->kota }})
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2 px-4 rounded shadow-sm transition text-sm">
                Terapkan Filter
            </button>

            @if(request('lokasi_id') || request('search'))
                <a href="{{ route('instruktur.index') }}" class="text-red-500 hover:text-red-700 hover:underline font-semibold text-sm transition">
                    Hapus Filter
                </a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profil Instruktur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penempatan Cabang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member Dilatih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($instrukturs as $i)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($i->foto)
                                <img src="{{ asset('storage/' . $i->foto) }}" alt="Foto {{ $i->nama }}" class="flex-shrink-0 h-12 w-12 rounded-full object-cover shadow-sm border border-gray-200">
                            @else
                                <div class="flex-shrink-0 h-12 w-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center font-bold text-lg border border-red-200">
                                    {{ substr($i->nama, 0, 1) }}
                                </div>
                            @endif
                            <div class="ml-4">
                                <div class="text-sm font-bold text-gray-900">{{ $i->nama }}</div>
                                <div class="text-sm text-gray-500">Username: {{ $i->username }}</div>
                                <div class="text-xs text-blue-600 font-semibold mt-1">{{ $i->spesialisasi ?? 'General Trainer' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($i->lokasi)
                            <span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-red-800 bg-red-100 rounded-full">
                                {{ $i->lokasi->nama_cabang }}
                            </span>
                        @else
                            <span class="inline-flex px-2 py-1 text-xs font-semibold leading-5 text-gray-500 bg-gray-100 rounded-full">
                                Belum ada penempatan
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-800 bg-blue-100 rounded-full">
                            {{ $i->member_paket_pts_count ?? 0 }} Member Aktif
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex gap-3">
                            <a href="{{ route('instruktur.clients', $i->instruktur_id) }}" class="text-green-600 hover:text-green-900 font-semibold">Daftar Client</a>
                            <a href="{{ route('instruktur.edit', $i->instruktur_id) }}" class="text-blue-600 hover:text-blue-900 font-semibold">Edit</a>
                            <form action="{{ route('instruktur.destroy', $i->instruktur_id) }}" method="POST" onsubmit="return confirm('Hapus instruktur ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">
                        Tidak ada instruktur yang ditemukan di cabang tersebut.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>