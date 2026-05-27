<x-app-layout>
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-900">Pengumuman & Promo</h2>
        <a href="{{ route('pengumuman.create') }}" class="bg-[#e45151] hover:bg-red-700 text-white font-semibold py-2 px-6 rounded shadow-sm">
            + Tambah Pengumuman
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($pengumumans as $item)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @if($item->foto)
                    <img src="{{ asset('storage/' . $item->foto) }}" class="w-full h-48 object-cover">
                @else
                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-400">No Image</div>
                @endif
                <div class="p-5">
                    <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($item->tanggal_post)->format('d M Y') }}</span>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $item->judul }}</h3>
                    <p class="text-gray-600 text-sm line-clamp-3 mb-4">{{ $item->deskripsi }}</p>
                    
                    <div class="flex items-center gap-4">
                        <a href="{{ route('pengumuman.edit', $item->pengumuman_id) }}" class="text-blue-500 text-sm font-semibold hover:underline">
                            Ubah
                        </a>

                        <form action="{{ route('pengumuman.destroy', $item->pengumuman_id) }}" method="POST" onsubmit="return confirm('Hapus pengumuman ini?')">
                            @csrf 
                            @method('DELETE')
                            <button type="submit" class="text-red-500 text-sm font-semibold hover:underline">
                                Hapus Pengumuman
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-gray-500 italic">Belum ada pengumuman.</p>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $pengumumans->links() }}
    </div>
</x-app-layout>