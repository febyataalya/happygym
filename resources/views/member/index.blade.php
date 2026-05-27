<x-app-layout>
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('dashboard') }}" class="inline-block bg-[#2bc466] hover:bg-green-600 text-white font-semibold py-2 px-6 rounded-full shadow-sm transition">
            < Kembali
        </a>
        </div>

    <h2 class="text-3xl font-bold text-gray-900 mb-6">Data Member</h2>

    <form method="GET" action="{{ route('member.index') }}" class="flex items-center gap-4 mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari member..." class="border-gray-300 rounded-md shadow-sm w-64 focus:ring-red-500 focus:border-red-500">
        <select name="bulan" class="border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
            <option value="">Semua Bulan</option>
            @php
                $bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            @endphp
            @foreach($bulanIndo as $index => $namaBulan)
                <option value="{{ $index + 1 }}" {{ request('bulan') == ($index + 1) ? 'selected' : '' }}>{{ $namaBulan }}</option>
            @endforeach
        </select>
        <select name="status_membership" class="border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
            <option value="">Semua Membership</option>
            <option value="Aktif" {{ request('status_membership') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
            <option value="Tidak Aktif" {{ request('status_membership') == 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
            <optgroup label="Berdasarkan Paket">
                @foreach($pakets as $paket)
                    <option value="paket_{{ $paket->paket_id }}" {{ request('status_membership') == 'paket_'.$paket->paket_id ? 'selected' : '' }}>Paket {{ $paket->nama_paket }}</option>
                @endforeach
            </optgroup>
        </select>
        <select name="lokasi_id" class="border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
            <option value="">Semua Cabang</option>
            @foreach($lokasis as $lokasi)
                <option value="{{ $lokasi->lokasi_id }}" {{ request('lokasi_id') == $lokasi->lokasi_id ? 'selected' : '' }}>{{ $lokasi->nama_cabang }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-[#e45151] hover:bg-red-600 text-white font-semibold py-2 px-6 rounded shadow-sm transition">
            Filter
        </button>
        @if(request()->anyFilled(['search', 'bulan', 'status_membership', 'lokasi_id']))
            <a href="{{ route('member.index') }}" class="text-gray-500 hover:text-gray-700 underline">Reset</a>
        @endif
    </form>

    <div class="mb-4"></div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs tracking-wider">No</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs tracking-wider text-center">Foto</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs tracking-wider">Nama & Kontak</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs tracking-wider text-center">Cabang</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs tracking-wider text-center">Status</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs tracking-wider text-center">Gym Umum</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs tracking-wider text-center">Personal Trainer</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs tracking-wider text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $index => $member)
                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                    <td class="py-4 px-6 text-gray-600">{{ $members->firstItem() + $index }}</td>
                    <td class="py-4 px-6 flex justify-center">
                        @if($member->foto)
                            <img src="{{ $member->foto_url }}" alt="Foto" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-xs border border-gray-200 shadow-sm">
                                No
                            </div>
                        @endif
                    </td>
                    <td class="py-4 px-6">
                        <div class="font-bold text-gray-800">{{ $member->nama }}</div>
                        <div class="text-sm text-gray-500">{{ $member->email }} <br> {{ $member->no_hp ?? '-' }}</div>
                    </td>
                    <td class="py-4 px-6 text-center text-sm font-semibold text-gray-600">
                        {{ $member->lokasi ? $member->lokasi->nama_cabang : '-' }}
                    </td>
                    <td class="py-4 px-6 text-center">
                        @if($member->status_membership == 'Aktif')
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Aktif</span>
                        @else
                            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold">Tidak Aktif</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-center text-gray-600 text-sm">
                        @if($member->status_membership == 'Aktif' && $member->tanggal_berakhir_member)
                            Aktif s/d <br> <strong>{{ \Carbon\Carbon::parse($member->tanggal_berakhir_member)->format('d M Y') }}</strong>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-center text-gray-600 text-sm">
                        @if($member->paketPts && $member->paketPts->count() > 0)
                            @php $pt = $member->paketPts->first(); @endphp
                            Sisa: <strong>{{ $pt->sisa_sesi }} Sesi</strong><br>
                            Coach: {{ $pt->instruktur ? $pt->instruktur->nama : 'Belum Ada' }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <a href="{{ route('member.show', $member->member_id) }}" class="text-green-500 hover:text-green-700 font-semibold text-sm">Detail</a>
                            
                            <a href="{{ route('member.edit', $member->member_id) }}" class="text-blue-500 hover:text-blue-700 font-semibold text-sm">Ubah</a>
                            
                            <form action="{{ route('member.destroy', $member->member_id) }}" method="POST" onsubmit="return confirm('Hapus member ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 font-semibold text-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-8 text-center text-gray-500">Belum ada data member.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $members->links() }}
    </div>
</x-app-layout>