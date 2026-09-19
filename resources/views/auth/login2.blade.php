<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - MARIMOI</title>
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#061522">
    <link href="{{ asset('frontend/favicon_io/favicon.ico') }}" rel="icon">
    <link href="{{ asset('frontend/favicon_io/apple-touch-icon.png') }}" rel="apple-touch-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500&display=swap"
        rel="stylesheet">
    @vite(['resources/css/spatial.css', 'resources/js/spatial.js'])
    <script src="https://js.hcaptcha.com/1/api.js?hl=id" async defer></script>
</head>

@php
    $inputBase = 'block w-full rounded-xl border bg-white py-3 pl-11 text-[15px] text-slate-900 placeholder:text-slate-400 transition focus:border-ocean focus:ring-4 focus:ring-ocean/15';
    $emailError = $errors->first('email');
    $passwordError = $errors->first('password');
    $captchaError = $errors->first('h-captcha-response');
@endphp

<body class="bg-mist font-manrope text-slate-900 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
        {{-- Panel merek --}}
        <aside class="relative isolate flex flex-col justify-between overflow-hidden bg-deep px-6 py-8 text-white sm:px-10 lg:p-14">
            <div class="pointer-events-none absolute inset-x-0 -inset-y-[8%] opacity-70" aria-hidden="true">
                <svg class="contours h-full w-full [&_path]:fill-none [&_path]:stroke-aqua/10 [&_path]:[vector-effect:non-scaling-stroke]"></svg>
            </div>
            <div class="pointer-events-none absolute -left-24 top-1/3 h-[520px] w-[520px] bg-[radial-gradient(closest-side,rgba(10,132,255,.22),transparent)]" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 opacity-60 [background-image:linear-gradient(rgba(32,217,255,.07)_1px,transparent_1px),linear-gradient(90deg,rgba(32,217,255,.07)_1px,transparent_1px)] [background-size:72px_72px] [mask-image:radial-gradient(ellipse_at_30%_50%,#000_20%,transparent_75%)]" aria-hidden="true"></div>

            <a href="{{ route('beranda') }}" class="relative inline-flex w-fit items-center gap-3 text-lg font-extrabold tracking-wider" aria-label="Kembali ke beranda MARIMOI">
                <img src="{{ asset('frontend/img/logo/logo-white.png') }}" alt="" class="h-9 w-auto">
                MARIMOI
            </a>

            <div class="relative my-10 max-lg:my-8 lg:my-0">
                <p class="reveal mb-4 flex items-center gap-3 font-grotesk text-xs uppercase tracking-widest text-aqua before:h-px before:w-7 before:bg-current" data-reveal>Spatial Intelligence Platform</p>
                <h1 class="reveal delay-100 max-w-[14ch] text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl" data-reveal>
                    Memetakan Masa Depan <span class="text-aqua">Maluku Utara.</span>
                </h1>
                <p class="reveal mt-6 hidden max-w-md text-lg text-white/70 delay-200 sm:block" data-reveal>Masuk untuk mengelola data spasial, memantau pembangunan, dan menindaklanjuti aspirasi masyarakat.</p>

                <ul class="reveal mt-8 hidden gap-3 text-[15px] text-white/80 delay-300 lg:grid" data-reveal>
                    @foreach (['Data spasial dan tematik terpadu', 'Pemantauan pembangunan berbasis peta', 'Tindak lanjut aspirasi masyarakat'] as $item)
                        <li class="flex items-center gap-3">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full border border-aqua/40 bg-aqua/10 text-aqua">
                                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                            </span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative hidden text-sm text-white/45 lg:block">&copy; {{ date('Y') }} BAPPEDA Provinsi Maluku Utara</p>
        </aside>

        {{-- Formulir --}}
        <section class="flex items-center justify-center px-6 py-12 sm:px-10 lg:p-14">
            <div class="reveal w-full max-w-md" data-reveal>
                <h2 class="text-3xl font-extrabold tracking-tight text-navy">Selamat datang</h2>
                <p class="mt-2 text-slate-600">Harap mengisi kredensial sebelum dapat masuk.</p>

                @if (session('status'))
                    <p class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
                @endif

                @if ($emailError)
                    <p class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        <svg viewBox="0 0 24 24" class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg>
                        <span>{{ $emailError }}</span>
                    </p>
                @endif

                <form id="loginForm" action="{{ route('login') }}" method="POST" class="mt-8 space-y-5" novalidate>
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-navy">Email</label>
                        <div class="relative">
                            <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-4 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 8 8 5 8-5"/></svg>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                placeholder="nama@instansi.go.id"
                                class="{{ $inputBase }} pr-4 {{ $emailError ? 'border-red-300' : 'border-slate-300' }}">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-navy">Password</label>
                        <div class="relative">
                            <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-4 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            <input type="password" id="password" name="password" required autocomplete="current-password"
                                placeholder="Masukkan password Anda"
                                class="{{ $inputBase }} pr-12 {{ $passwordError ? 'border-red-300' : 'border-slate-300' }}">
                            <button type="button" id="togglePassword" aria-label="Tampilkan password" aria-pressed="false"
                                class="absolute right-2 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 transition-colors hover:text-ocean">
                                <svg data-eye viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg data-eye-off viewBox="0 0 24 24" class="hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 5.1A9.8 9.8 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.2M6.6 6.6A16.5 16.5 0 0 0 2 12s3.5 7 10 7c1.7 0 3.2-.4 4.5-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                            </button>
                        </div>
                        @if ($passwordError)
                            <p class="mt-2 text-sm text-red-600">{{ $passwordError }}</p>
                        @endif
                    </div>

                    <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-600">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded border-slate-300 text-ocean focus:ring-ocean/30">
                        Ingat saya
                    </label>

                    <div>
                        <div class="flex justify-center overflow-hidden">
                            <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}"></div>
                        </div>
                        @if ($captchaError)
                            <p class="mt-2 text-center text-sm text-red-600" role="alert">{{ $captchaError }}</p>
                        @endif
                    </div>

                    <button type="submit" id="loginSubmit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-ocean px-6 py-3.5 text-[15px] font-bold text-white shadow-[0_10px_30px_-12px_rgba(10,132,255,.8)] transition duration-300 ease-out hover:-translate-y-0.5 hover:shadow-[0_14px_38px_-10px_rgba(32,217,255,.75)] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0">
                        <span data-label>Masuk</span>
                    </button>
                </form>

                <div class="my-7 flex items-center gap-4 text-xs uppercase tracking-widest text-slate-400 before:h-px before:flex-1 before:bg-slate-300 after:h-px after:flex-1 after:bg-slate-300">atau</div>

                <a href="{{ route('login.google') }}"
                    class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-300 bg-white px-6 py-3.5 text-[15px] font-semibold text-slate-800 transition duration-300 hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-md">
                    <svg viewBox="0 0 48 48" class="h-5 w-5" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5Z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6Z"/><path fill="#FBBC05" d="M10.5 28.7A14.5 14.5 0 0 1 9.5 24c0-1.6.3-3.2.8-4.7l-7.9-6.1A24 24 0 0 0 0 24c0 3.9.9 7.5 2.6 10.8l7.9-6.1Z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.9 2.3-8.3 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48Z"/></svg>
                    Masuk dengan Google
                </a>

                <p class="mt-8 text-center text-sm text-slate-500">
                    <a href="{{ route('beranda') }}" class="inline-flex items-center gap-1.5 font-semibold text-ocean underline decoration-ocean/30 underline-offset-4 transition-colors hover:decoration-ocean">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                        Kembali ke beranda
                    </a>
                </p>
                <p class="mt-6 text-center text-xs text-slate-400 lg:hidden">&copy; {{ date('Y') }} BAPPEDA Provinsi Maluku Utara</p>
            </div>
        </section>
    </main>

    <script>
        (function () {
            var input = document.getElementById('password');
            var toggle = document.getElementById('togglePassword');
            toggle.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
                toggle.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
                toggle.querySelector('[data-eye]').classList.toggle('hidden', show);
                toggle.querySelector('[data-eye-off]').classList.toggle('hidden', !show);
            });
            document.getElementById('loginForm').addEventListener('submit', function () {
                var btn = document.getElementById('loginSubmit');
                btn.disabled = true;
                btn.querySelector('[data-label]').textContent = 'Memproses…';
            });
        })();
    </script>
</body>

</html>
