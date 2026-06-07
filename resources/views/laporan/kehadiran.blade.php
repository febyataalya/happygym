<x-app-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Laporan Kehadiran Member</h2>

        <!-- Cards Row -->
        <div class="flex flex-wrap gap-4 mb-6">
            <!-- Total Kehadiran Card -->
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 flex items-center gap-4 min-w-[240px]">
                <div class="w-14 h-14 rounded-xl bg-teal-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">TOTAL KUNJUNGAN / KEHADIRAN</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ $totalKehadiran }}</h3>
                </div>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Filter Kehadiran</h3>
            <form action="{{ route('laporan.kehadiran') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end mb-6">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Cari Member</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama member..." class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Cabang</label>
                    <select name="lokasi_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-sm">
                        <option value="">Semua Cabang</option>
                        @foreach($lokasis as $l)
                            <option value="{{ $l->lokasi_id }}" {{ request('lokasi_id') == $l->lokasi_id ? 'selected' : '' }}>{{ $l->nama_cabang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-[#e45151] hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow-sm transition text-sm flex-1">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'tanggal_mulai', 'tanggal_selesai', 'lokasi_id']))
                        <a href="{{ route('laporan.kehadiran') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-600 font-bold py-2 px-4 rounded shadow-sm transition text-sm flex items-center justify-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <div class="flex gap-4 pt-4 border-t border-gray-100">
                <a href="{{ route('laporan.kehadiran.export.excel', request()->query()) }}" class="inline-block bg-[#2bc466] hover:bg-green-600 text-white font-bold py-2 px-6 rounded shadow-sm transition text-sm">
                    Export Excel
                </a>
                <a href="{{ route('laporan.kehadiran.export.pdf', request()->query()) }}" class="inline-block bg-[#e45151] hover:bg-red-600 text-white font-bold py-2 px-6 rounded shadow-sm transition text-sm">
                    Export PDF
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">NO</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">TANGGAL</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">NAMA MEMBER</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">CABANG</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">JAM MASUK</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">JAM KELUAR</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kehadirans as $index => $k)
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                            <td class="py-4 px-6 text-gray-600 text-center">{{ $kehadirans->firstItem() + $index }}</td>
                            <td class="py-4 px-6 text-sm text-gray-700">
                                {{ \Carbon\Carbon::parse($k->tanggal)->format('d M Y') }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800">{{ $k->member->nama ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $k->member->email ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-6 text-center text-sm font-semibold text-gray-600">
                                {{ $k->lokasi ? $k->lokasi->nama_cabang : '-' }}
                            </td>
                            <td class="py-4 px-6 text-center text-sm text-gray-600 font-mono">
                                {{ $k->waktu_masuk ?? '-' }}
                            </td>
                            <td class="py-4 px-6 text-center text-sm text-gray-600 font-mono">
                                {{ $k->waktu_keluar ?? '-' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($k->status_kunjungan == 'Masuk')
                                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold uppercase">
                                        Masuk / Latihan
                                    </span>
                                @else
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold uppercase">
                                        Selesai
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-500">Belum ada data kehadiran yang sesuai filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $kehadirans->links() }}
        </div>
    </div>
</x-app-layout>
