<aside class="w-64 bg-white flex flex-col justify-between h-screen shadow-[4px_0_24px_rgba(0,0,0,0.03)] flex-shrink-0">
    <div class="flex-1 overflow-y-auto">
        <div class="flex items-center justify-center h-28 border-b border-gray-100 p-4">
            <a href="{{ route('dashboard') }}" class="block">
                {{-- 
                    Mengambil logo dari public storage: storage/images/logo.png
                    w-auto: lebar otomatis menyesuaikan aspek rasio gambar
                    h-16: tinggi dibatasi (64px) agar serasi dengan container h-28
                    object-contain: memastikan gambar tidak gepeng/stretch
                    mx-auto: memastikan gambar di tengah
                --}}
                <img src="{{ asset('storage/images/logo.png') }}" 
                     alt="Happy Gym Logo" 
                     class="w-auto h-16 object-contain mx-auto">
            </a>
        </div>

        <nav class="mt-8 px-4 space-y-2">
            <a href="{{ url('/dashboard') }}" class="block py-3 px-4 rounded-lg transition duration-200 {{ request()->is('dashboard*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                Dashboard
            </a>
            <a href="{{ url('/member') }}" class="block py-3 px-4 rounded-lg transition duration-200 {{ request()->is('member*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                Member
            </a>
            <a href="{{ url('/instruktur') }}" class="block py-3 px-4 rounded-lg transition duration-200 {{ request()->is('instruktur*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                Personal Trainer
            </a>
            <a href="{{ url('/paket') }}" class="block py-3 px-4 rounded-lg transition duration-200 {{ request()->is('paket*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                Paket
            </a>
            <a href="{{ url('/transaksi') }}" class="block py-3 px-4 rounded-lg transition duration-200 {{ request()->is('transaksi*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                Transaksi
            </a>
            <a href="{{ url('/pengumuman') }}" class="block py-3 px-4 rounded-lg transition duration-200 {{ request()->is('pengumuman*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                Pengumuman
            </a>
            <a href="{{ url('/lokasi') }}" class="block py-3 px-4 rounded-lg transition duration-200 {{ request()->is('lokasi*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                Lokasi
            </a>
            <a href="{{ url('/diskon') }}" class="block py-3 px-4 rounded-lg transition duration-200 {{ request()->is('diskon*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                Kelola Diskon
            </a>

            <!-- Menu Laporan (Dropdown) -->
            <div x-data="{ open: {{ request()->is('laporan*') ? 'true' : 'false' }} }">
                <button @click="open = !open" type="button" class="w-full flex justify-between items-center py-3 px-4 rounded-lg transition duration-200 {{ request()->is('laporan*') ? 'bg-red-50 text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                    <span>Laporan</span>
                    <svg :class="{'rotate-180': open}" style="width: 16px; height: 16px; transition: transform 0.2s;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" style="display: {{ request()->is('laporan*') ? 'block' : 'none' }};" class="mt-1 pl-4 space-y-1">
                    <a href="{{ url('/laporan/member') }}" class="block py-2 px-4 rounded-lg transition duration-200 text-sm {{ request()->is('laporan/member*') ? 'text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                        Laporan Member
                    </a>
                    <a href="{{ url('/laporan/transaksi') }}" class="block py-2 px-4 rounded-lg transition duration-200 text-sm {{ request()->is('laporan/transaksi*') ? 'text-[#db3535] font-semibold' : 'text-gray-500 hover:bg-gray-50 hover:text-[#db3535]' }}">
                        Laporan Transaksi
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <div class="p-6 mb-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full bg-[#db3535] hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition duration-200">
                Logout
            </button>
        </form>
    </div>
</aside>