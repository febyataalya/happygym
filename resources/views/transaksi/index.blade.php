<x-app-layout>
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-900">Riwayat Transaksi</h2>
            <p class="text-gray-500 text-sm">Semua pembayaran otomatis melalui Midtrans.</p>
        </div>
        <div class="flex gap-3">
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('transaksi.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Status</label>
                <select name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="settlement" {{ request('status') == 'settlement' ? 'selected' : '' }}>Settlement / Berhasil</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="expire" {{ request('status') == 'expire' ? 'selected' : '' }}>Expired</option>
                    <option value="cancel" {{ request('status') == 'cancel' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Cabang</label>
                <select name="lokasi_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 text-sm">
                    <option value="">Semua Cabang</option>
                    @foreach($lokasis as $l)
                        <option value="{{ $l->lokasi_id }}" {{ request('lokasi_id') == $l->lokasi_id ? 'selected' : '' }}>{{ $l->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 hover:bg-black text-white font-bold py-2 px-4 rounded shadow-sm transition text-sm flex-1">
                    Filter
                </button>
                @if(request()->anyFilled(['tanggal_mulai', 'tanggal_selesai', 'status', 'lokasi_id']))
                    <a href="{{ route('transaksi.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded shadow-sm transition text-sm">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs">Order ID</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs">Tanggal</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs">Member / Cabang</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs">Paket</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs">Jumlah</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs text-center">Status</th>
                    <th class="py-4 px-6 font-bold text-gray-700 uppercase text-xs text-center">Metode</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaksis as $t)
                <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                    <td class="py-4 px-6 text-sm font-mono text-gray-600">{{ $t->order_id }}</td>
                    <td class="py-4 px-6 text-xs text-gray-500">{{ $t->created_at->format('d M Y H:i') }}</td>
                    <td class="py-4 px-6">
                        <div class="font-medium text-gray-800">{{ $t->member->nama }}</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase">{{ $t->member->lokasi->nama_cabang ?? '-' }}</div>
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
                    <td class="py-4 px-6 text-center">
                        <a href="{{ route('transaksi.show', $t->pembayaran_id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-4 rounded text-xs transition">
                            Lihat Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="py-12 text-center text-gray-500 italic">Belum ada transaksi masuk.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>