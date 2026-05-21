<x-app-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Laporan Transaksi</h2>

        <!-- Cards Row -->
        <div class="flex flex-wrap gap-4 mb-6">
            
            <!-- Card: Total Pendapatan -->
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 flex items-center gap-4 min-w-[240px]">
                <div class="w-14 h-14 rounded-xl bg-[#db3535] flex-shrink-0"></div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">TOTAL PENDAPATAN</p>
                    <h3 class="text-3xl font-black text-gray-900">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</h3>
                </div>
            </div>

        </div>

        <!-- Filter Row -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Filter Laporan</h3>
            <form action="{{ route('laporan.transaksi') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end mb-6">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-[#e45151] focus:border-[#e45151] text-sm">
                        <option value="">Semua Status</option>
                        <option value="settlement" {{ request('status') == 'settlement' ? 'selected' : '' }}>Settlement / Berhasil</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="expire" {{ request('status') == 'expire' ? 'selected' : '' }}>Expired</option>
                        <option value="cancel" {{ request('status') == 'cancel' ? 'selected' : '' }}>Cancelled</option>
                    </select>
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
                    @if(request()->anyFilled(['tanggal_mulai', 'tanggal_selesai', 'status', 'lokasi_id']))
                        <a href="{{ route('laporan.transaksi') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-600 font-bold py-2 px-4 rounded shadow-sm transition text-sm flex items-center justify-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <div class="flex gap-4 pt-4 border-t border-gray-100">
                <a href="{{ route('laporan.transaksi.export.excel', request()->query()) }}" class="inline-block bg-[#2bc466] hover:bg-green-600 text-white font-bold py-2 px-6 rounded shadow-sm transition">
                    Export Excel
                </a>
                <a href="{{ route('laporan.transaksi.export.pdf', request()->query()) }}" class="inline-block bg-[#e45151] hover:bg-red-600 text-white font-bold py-2 px-6 rounded shadow-sm transition">
                    Export PDF
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">ORDER ID</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">TANGGAL</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">MEMBER / CABANG</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">PAKET</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider">JUMLAH</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">STATUS</th>
                            <th class="py-4 px-6 font-bold text-gray-600 uppercase text-xs tracking-wider text-center">METODE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transaksis as $t)
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                            <td class="py-4 px-6 text-sm font-mono text-gray-600">{{ $t->order_id }}</td>
                            <td class="py-4 px-6 text-xs text-gray-500">{{ $t->created_at->format('d M Y H:i') }}</td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800">{{ $t->member->nama ?? '-' }}</div>
                                <div class="text-[10px] text-gray-500 font-bold uppercase">{{ $t->member->lokasi->nama_cabang ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-6 text-sm text-gray-600">{{ $t->pemesanan->paket->nama_paket ?? '-' }}</td>
                            <td class="py-4 px-6 font-bold">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                            <td class="py-4 px-6 text-center">
                                @php
                                    $color = [
                                        'settlement' => 'bg-green-100 text-green-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'expire' => 'bg-red-100 text-red-700',
                                        'cancel' => 'bg-gray-100 text-gray-700',
                                    ][$t->status] ?? 'bg-blue-100 text-blue-700';
                                @endphp
                                <span class="{{ $color }} px-3 py-1 rounded-full text-xs font-bold uppercase">
                                    {{ $t->status }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-xs text-gray-500 uppercase">
                                {{ $t->metode ?? 'N/A' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-500 italic">Belum ada data transaksi yang sesuai filter.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
