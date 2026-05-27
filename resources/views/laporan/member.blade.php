<x-app-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Laporan Data Member</h2>

        <!-- Cards Row -->
        <div class="flex flex-wrap gap-4 mb-6">
            <!-- Member Tidak Aktif Card -->
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 flex items-center gap-4 min-w-[240px]">
                <div class="w-14 h-14 rounded-xl bg-gray-400 flex-shrink-0"></div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">MEMBER TIDAK AKTIF</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ $memberTidakAktif }}</h3>
                </div>
            </div>

            <!-- Member Aktif Card -->
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 flex items-center gap-4 min-w-[240px]">
                <div class="w-14 h-14 rounded-xl bg-[#00a65a] flex-shrink-0"></div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">MEMBER AKTIF</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ $memberAktif }}</h3>
                </div>
            </div>

            <!-- Total Member Card -->
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 flex items-center gap-4 min-w-[240px]">
                <div class="w-14 h-14 rounded-xl bg-blue-600 flex-shrink-0"></div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">TOTAL MEMBER</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ $totalMember }}</h3>
                </div>
            </div>
        </div>

        <!-- Filter Row -->
        <form method="GET" action="{{ route('laporan.member') }}" class="flex flex-wrap items-center gap-3 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari member..." class="border-gray-200 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-gray-600 min-w-[200px]">
            
            <select name="bulan" class="border-gray-200 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-gray-600 min-w-[150px]">
                <option value="">Semua Bulan</option>
                @php
                    $bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                @endphp
                @foreach($bulanIndo as $index => $namaBulan)
                    @php $bulanVal = sprintf('%02d', $index + 1); @endphp
                    <option value="{{ $bulanVal }}" {{ request('bulan') == $bulanVal ? 'selected' : '' }}>{{ $namaBulan }}</option>
                @endforeach
            </select>

            <select name="status_membership" class="border-gray-200 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-gray-600 min-w-[180px]">
                <option value="">Semua Membership</option>
                <option value="Aktif" {{ request('status_membership') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="Tidak Aktif" {{ request('status_membership') == 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                <optgroup label="Berdasarkan Paket">
                    @foreach($pakets as $paket)
                        <option value="paket_{{ $paket->paket_id }}" {{ request('status_membership') == 'paket_'.$paket->paket_id ? 'selected' : '' }}>Paket {{ $paket->nama_paket }}</option>
                    @endforeach
                </optgroup>
            </select>

            <select name="lokasi_id" class="border-gray-200 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-gray-600 min-w-[200px]">
                <option value="">Semua Cabang</option>
                @foreach($lokasis as $lokasi)
                    <option value="{{ $lokasi->lokasi_id }}" {{ request('lokasi_id') == $lokasi->lokasi_id ? 'selected' : '' }}>{{ $lokasi->nama_cabang }}</option>
                @endforeach
            </select>
            
            <button type="submit" class="bg-[#e45151] hover:bg-red-700 text-white font-bold py-2 px-6 rounded shadow-sm transition">
                Filter
            </button>
        </form>

        <!-- Actions Row -->
        <div class="flex gap-3 mb-6">
            <a href="{{ route('laporan.member.export.excel', request()->query()) }}" class="inline-block bg-[#2bc466] hover:bg-green-600 text-white font-bold py-2 px-4 rounded shadow-sm transition">
                Export Excel
            </a>
            <a href="{{ route('laporan.member.export.pdf', request()->query()) }}" class="inline-block bg-[#e45151] hover:bg-red-600 text-white font-bold py-2 px-4 rounded shadow-sm transition">
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
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">NAMA & KONTAK</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">CABANG</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">STATUS</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">GYM UMUM</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">PERSONAL TRAINER</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $index => $member)
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                            <td class="py-4 px-6 text-gray-600 text-center">{{ $members->firstItem() + $index }}</td>
                            <td class="py-4 px-6 text-center">
                                @if($member->foto)
                                    <img src="{{ asset('storage/' . $member->foto) }}" alt="Foto" class="w-10 h-10 rounded-full object-cover mx-auto">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mx-auto text-gray-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
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
                                @if($member->status_membership == 'Aktif' && $member->tanggal_berakhir_member && \Carbon\Carbon::parse($member->tanggal_berakhir_member)->isFuture())
                                    <span class="text-green-600 font-bold text-sm">Aktif</span>
                                @else
                                    <span class="text-gray-500 font-bold text-sm">Tidak Aktif</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center text-gray-600 text-sm">
                                @if($member->status_membership == 'Aktif' && $member->tanggal_berakhir_member && \Carbon\Carbon::parse($member->tanggal_berakhir_member)->isFuture())
                                    Aktif s/d <br> <strong>{{ \Carbon\Carbon::parse($member->tanggal_berakhir_member)->format('d M Y') }}</strong>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center text-gray-600 text-sm">
                                @if($member->paketPts && $member->paketPts->count() > 0)
                                    @php $pt = $member->paketPts->first(); @endphp
                                    @if($pt->status == 'Aktif')
                                        Sisa: <strong>{{ $pt->sisa_sesi }} Sesi</strong><br>
                                        Coach: {{ $pt->instruktur ? $pt->instruktur->nama : 'Belum Ada' }}
                                    @else
                                        <span class="text-gray-400">Habis</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                <a href="{{ route('member.show', $member->member_id) }}" class="inline-flex items-center gap-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-1.5 px-3 rounded text-xs transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-500">Belum ada data member.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $members->links() }}
        </div>
    </div>
</x-app-layout>
