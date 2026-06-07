<x-app-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Laporan Data Personal Trainer (PT)</h2>

        <!-- Cards Row -->
        <div class="flex flex-wrap gap-4 mb-6">
            <!-- Total PT Card -->
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 flex items-center gap-4 min-w-[240px]">
                <div class="w-14 h-14 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">TOTAL PERSONAL TRAINER</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ $totalPt }}</h3>
                </div>
            </div>
        </div>

        <!-- Filter Row -->
        <form method="GET" action="{{ route('laporan.pt') }}" class="flex flex-wrap items-center gap-3 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama PT atau spesialisasi..." class="border-gray-200 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-gray-600 min-w-[250px] text-sm">
            
            <select name="lokasi_id" class="border-gray-200 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-gray-600 min-w-[200px] text-sm">
                <option value="">Semua Cabang</option>
                @foreach($lokasis as $lokasi)
                    <option value="{{ $lokasi->lokasi_id }}" {{ request('lokasi_id') == $lokasi->lokasi_id ? 'selected' : '' }}>{{ $lokasi->nama_cabang }}</option>
                @endforeach
            </select>
            
            <button type="submit" class="bg-[#e45151] hover:bg-red-700 text-white font-bold py-2 px-6 rounded shadow-sm transition text-sm">
                Filter
            </button>
            @if(request()->anyFilled(['search', 'lokasi_id']))
                <a href="{{ route('laporan.pt') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-600 font-bold py-2 px-4 rounded shadow-sm transition text-sm flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>

        <!-- Actions Row -->
        <div class="flex gap-3 mb-6">
            <a href="{{ route('laporan.pt.export.excel', request()->query()) }}" class="inline-block bg-[#2bc466] hover:bg-green-600 text-white font-bold py-2 px-4 rounded shadow-sm transition text-sm">
                Export Excel
            </a>
            <a href="{{ route('laporan.pt.export.pdf', request()->query()) }}" class="inline-block bg-[#e45151] hover:bg-red-600 text-white font-bold py-2 px-4 rounded shadow-sm transition text-sm">
                Export PDF
            </a>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">NO</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">FOTO</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">NAMA PERSONAL TRAINER</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">SPESIALISASI</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">CABANG</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">CLIENT AKTIF</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">RATING RATA-RATA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pts as $index => $pt)
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                            <td class="py-4 px-6 text-gray-600 text-center">{{ $pts->firstItem() + $index }}</td>
                            <td class="py-4 px-6 text-center">
                                @if($pt->foto)
                                    <img src="{{ asset('storage/' . $pt->foto) }}" alt="Foto" class="w-10 h-10 rounded-full object-cover mx-auto">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mx-auto text-gray-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800">{{ $pt->nama }}</div>
                                <div class="text-xs text-gray-500">Username: {{ $pt->username }}</div>
                            </td>
                            <td class="py-4 px-6 text-sm text-gray-600">
                                {{ $pt->spesialisasi ?? '-' }}
                            </td>
                            <td class="py-4 px-6 text-center text-sm font-semibold text-gray-600">
                                {{ $pt->lokasi ? $pt->lokasi->nama_cabang : '-' }}
                            </td>
                            <td class="py-4 px-6 text-center text-sm font-bold text-gray-800">
                                {{ $pt->active_clients_count }} Client
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <span class="text-sm font-bold text-gray-800">{{ $pt->rating_avg }}</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-500">Belum ada data Personal Trainer (PT) yang sesuai filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $pts->links() }}
        </div>
    </div>
</x-app-layout>
