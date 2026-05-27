<x-app-layout>
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('instruktur.index') }}" class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-6 rounded-full transition">
                < Kembali
            </a>
            <h2 class="text-3xl font-bold text-gray-900">Daftar Member yang Dilatih</h2>
        </div>
    </div>

    <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-xl shadow-sm mb-6 flex items-center gap-6">
        @if($instruktur->foto)
            <img src="{{ asset('storage/' . $instruktur->foto) }}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md">
        @endif
        <div>
            <div class="text-xl font-bold text-gray-900">Coach: {{ $instruktur->nama }}</div>
            <div class="text-gray-600">Spesialisasi: {{ $instruktur->spesialisasi ?? 'Instruktur Umum' }}</div>
            <div class="text-sm font-semibold text-red-600 mt-1">Total Client Aktif: {{ $clients->count() }} Orang</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">No</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Nama Member</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Kontak</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Sisa Sesi</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Expired Paket</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($clients as $index => $client)
                @php $paket = $client->paketPts->first(); @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $clients->firstItem() + $index }}</td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-gray-900">{{ $client->nama }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-600">{{ $client->email }}</div>
                        <div class="text-xs text-gray-400">{{ $client->no_hp ?? '-' }}</div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded-full text-xs">
                            {{ $paket->sisa_sesi }} Sesi
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($paket->expired_date)->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 text-center text-sm">
                        <a href="{{ route('member.show', $client->member_id) }}" class="text-red-500 hover:text-red-700 font-bold">Detail Member</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-400 italic">
                        Belum ada member yang dilatih oleh instruktur ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $clients->links() }}
    </div>
</x-app-layout>
