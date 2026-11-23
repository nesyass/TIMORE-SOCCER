<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Timore Soccer School</title>

    <!-- Google Fonts: Barlow Condensed (Judul) & Plus Jakarta Sans (Body/Input) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Assets (Sesuaikan jika menggunakan CDN Tailwind saat development) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0D1015;
        }
        .font-heading {
            font-family: 'Barlow Condensed', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between bg-[#0D1015] text-white antialiased selection:bg-[#F8B62D] selection:text-[#111318]">

    <!-- Top Header / Brand Logo -->
    <header class="w-full px-6 py-5 sm:px-10 sm:py-6">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-3.5 group">
            <!-- Wadah Logo -->
            <div class="w-11 h-11 rounded-xl bg-white p-1.5 flex items-center justify-center shadow-md shrink-0 transition-transform duration-200 group-hover:scale-105">
                {{-- Ganti src dengan path logo asli di folder public/images/ --}}
                <img 
                    src="{{ asset('images/logo-timore.png') }}" 
                    alt="Logo Timore Soccer School" 
                    class="w-full h-full object-contain"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >
                <!-- Fallback Icon jika file gambar belum dimasukkan -->
                <div class="hidden w-full h-full items-center justify-center rounded-lg bg-[#111318] text-[#F8B62D] font-heading font-extrabold text-sm">
                    TSS
                </div>
            </div>

            <!-- Teks Brand -->
            <div class="flex flex-col">
                <span class="font-heading font-extrabold text-xl sm:text-[22px] leading-none tracking-wide text-white uppercase">
                    TIMORE SOCCER SCHOOL
                </span>
                <span class="text-[10px] sm:text-[11px] font-bold tracking-[0.08em] text-[#F8B62D] uppercase mt-1">
                    PUSAT PEMBINAAN SEPAKBOLA UNGGUL
                </span>
            </div>
        </a>
    </header>

    <!-- Main Content: Login Card -->
    <main class="flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-[440px] bg-[#161A22] border border-[#232834] rounded-2xl px-6 py-9 sm:px-10 sm:py-11 shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
            
            <!-- Card Header -->
            <div class="text-center mb-8">
                <h1 class="font-heading font-extrabold text-3xl sm:text-[34px] tracking-wide text-white uppercase leading-tight">
                    SELAMAT DATANG!
                </h1>
                <p class="text-xs sm:text-[13px] text-[#8E96A4] mt-2 leading-relaxed max-w-[290px] mx-auto">
                    Masuk ke akun Timore Soccer School untuk mengakses informasi latihan.
                </p>
            </div>

            <!-- Login Form -->
            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Input: Email atau Nomor HP -->
                <div>
                    <label for="login" class="block text-[11px] font-bold tracking-wider text-[#D1D5DB] uppercase mb-2">
                        EMAIL ATAU NOMOR HP
                    </label>
                    <div class="relative">
                        <!-- Icon @ -->
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#6B7280]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>

                        <input 
                            type="text" 
                            id="login" 
                            name="login" 
                            value="{{ old('login') }}"
                            placeholder="Masukkan email atau nomor HP"
                            required 
                            autofocus
                            class="w-full bg-[#1D222C] border @error('login') border-red-500 @else border-[#2B313E] @enderror rounded-lg pl-10 pr-4 py-3 text-xs sm:text-sm text-white placeholder-[#5A6270] focus:outline-none focus:border-[#F8B62D] focus:ring-1 focus:ring-[#F8B62D] transition-colors"
                        >
                    </div>
                    @error('login')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Input: Password -->
                <div>
                    <label for="password" class="block text-[11px] font-bold tracking-wider text-[#D1D5DB] uppercase mb-2">
                        PASSWORD
                    </label>
                    <div class="relative">
                        <!-- Icon Gembok -->
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#6B7280]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>

                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Masukkan password"
                            required
                            class="w-full bg-[#1D222C] border @error('password') border-red-500 @else border-[#2B313E] @enderror rounded-lg pl-10 pr-10 py-3 text-xs sm:text-sm text-white placeholder-[#5A6270] focus:outline-none focus:border-[#F8B62D] focus:ring-1 focus:ring-[#F8B62D] transition-colors"
                        >

                        <!-- Toggle Show/Hide Password -->
                        <button 
                            type="button" 
                            id="togglePassword"
                            aria-label="Tampilkan atau sembunyikan password"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-[#8E96A4] hover:text-white focus:outline-none transition-colors"
                        >
                            <!-- Icon Mata Terbuka -->
                            <svg id="eyeOpenIcon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <!-- Icon Mata Tertutup -->
                            <svg id="eyeClosedIcon" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror

                    <!-- Link Lupa Password -->
                    <div class="flex justify-end mt-2.5">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs font-medium text-[#F8B62D] hover:text-[#FCD34D] transition-colors">
                                Lupa Password?
                            </a>
                        @else
                            <a href="#" class="text-xs font-medium text-[#F8B62D] hover:text-[#FCD34D] transition-colors">
                                Lupa Password?
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-1">
                    <button 
                        type="submit" 
                        class="w-full bg-[#F8B62D] hover:bg-[#F59E0B] active:scale-[0.99] text-[#111318] font-heading font-extrabold text-base tracking-wider uppercase py-3 rounded-xl flex items-center justify-center gap-2 shadow-lg shadow-[#F8B62D]/10 transition-all duration-150 cursor-pointer"
                    >
                        <span>MASUK</span>
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Register Link -->
            <p class="mt-6 text-center text-xs text-[#8E96A4]">
                Belum punya akun?
                <a href="{{ Route::has('register') ? route('register') : '#' }}" class="font-semibold text-[#F8B62D] hover:underline ml-0.5">
                    Daftar Sekarang
                </a>
            </p>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full border-t border-[#1D222C] px-6 py-5 sm:px-10">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] sm:text-xs text-[#6B7280]">
            <p>&copy; {{ date('Y') }} Timore Soccer School. Hak Cipta Dilindungi.</p>
            <div class="flex items-center gap-3">
                <a href="#" class="hover:text-[#D1D5DB] transition-colors">Kebijakan Privasi</a>
                <span class="text-[#374151]">&bull;</span>
                <a href="#" class="hover:text-[#D1D5DB] transition-colors">Syarat &amp; Ketentuan</a>
            </div>
        </div>
    </footer>

    <!-- Script Toggle Password -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.getElementById('eyeOpenIcon');
            const eyeClosed = document.getElementById('eyeClosedIcon');

            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                eyeOpen.classList.toggle('hidden', isPassword);
                eyeClosed.classList.toggle('hidden', !isPassword);
            });
        });
    </script>
</body>
</html>