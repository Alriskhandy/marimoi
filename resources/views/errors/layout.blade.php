@php
    $code = $code ?? 500;
    $accent = $accent ?? 'blue';
    $title = $title ?? 'Terjadi Kesalahan';
    $message = $message ?? 'Terjadi kesalahan pada halaman ini.';
    $description = $description ?? 'Silakan kembali ke beranda atau coba beberapa saat lagi.';
    $canReload = $canReload ?? false;
    $icons = [
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M8.5 11h5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'server' => '<rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 7h.01M7 17h.01"/>',
        'tool' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.7 2.7-2.3-.7-.7-2.3z"/>',
    ];
    $iconPaths = $icons[$icon ?? 'server'] ?? $icons['server'];
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ $title }} | MARIMOI</title>
    <link rel="shortcut icon" href="{{ asset('frontend/img/logo/logo-white.png') }}">
    <style>
        :root {
            --bg: #f4f6fb;
            --bg-glow-1: rgba(59, 130, 246, .14);
            --bg-glow-2: rgba(16, 185, 129, .10);
            --card: #ffffff;
            --card-border: #e5e9f2;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --ghost-bg: #eef2f9;
            --ghost-hover: #e2e8f4;
            --shadow: 0 20px 50px -20px rgba(15, 23, 42, .25);
            --accent: #2563eb;
            --accent-soft: rgba(37, 99, 235, .12);
        }

        :root[data-accent="amber"] { --accent: #d97706; --accent-soft: rgba(217, 119, 6, .14); }
        :root[data-accent="red"] { --accent: #dc2626; --accent-soft: rgba(220, 38, 38, .12); }
        :root[data-accent="violet"] { --accent: #7c3aed; --accent-soft: rgba(124, 58, 237, .12); }
        :root[data-accent="slate"] { --accent: #475569; --accent-soft: rgba(71, 85, 105, .14); }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b1120;
                --bg-glow-1: rgba(59, 130, 246, .20);
                --bg-glow-2: rgba(16, 185, 129, .12);
                --card: #131c31;
                --card-border: #24304b;
                --text: #f1f5f9;
                --muted: #94a3b8;
                --primary: #3b82f6;
                --primary-hover: #60a5fa;
                --ghost-bg: #1b2742;
                --ghost-hover: #24335a;
                --shadow: 0 20px 50px -20px rgba(0, 0, 0, .7);
            }

            :root[data-accent="amber"] { --accent: #fbbf24; --accent-soft: rgba(251, 191, 36, .14); }
            :root[data-accent="red"] { --accent: #f87171; --accent-soft: rgba(248, 113, 113, .14); }
            :root[data-accent="violet"] { --accent: #a78bfa; --accent-soft: rgba(167, 139, 250, .14); }
            :root[data-accent="slate"] { --accent: #94a3b8; --accent-soft: rgba(148, 163, 184, .14); }
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background:
                radial-gradient(circle at 15% 85%, var(--bg-glow-1), transparent 45%),
                radial-gradient(circle at 85% 15%, var(--bg-glow-2), transparent 45%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .error-card {
            width: 100%;
            max-width: 520px;
            text-align: center;
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 2.5rem 2rem 2rem;
            animation: rise .5s ease both;
        }

        .error-icon {
            width: 76px;
            height: 76px;
            margin: 0 auto 1.25rem;
            border-radius: 22px;
            display: grid;
            place-items: center;
            background: var(--accent-soft);
            color: var(--accent);
        }

        .error-icon svg {
            width: 38px;
            height: 38px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .error-code {
            font-size: clamp(4rem, 16vw, 6.5rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: .04em;
            color: var(--accent);
            margin-bottom: .75rem;
        }

        .error-title {
            font-size: clamp(1.25rem, 4vw, 1.6rem);
            font-weight: 700;
            margin-bottom: .6rem;
        }

        .error-message {
            font-size: 1rem;
            color: var(--text);
            line-height: 1.55;
            margin-bottom: .5rem;
        }

        .error-description {
            font-size: .93rem;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1.4rem;
            font: inherit;
            font-size: .95rem;
            font-weight: 600;
            text-decoration: none;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            transition: background .2s, transform .2s;
        }

        .btn:hover { transform: translateY(-1px); }
        .btn:focus-visible { outline: 3px solid var(--accent-soft); outline-offset: 2px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-ghost { background: var(--ghost-bg); color: var(--text); }
        .btn-ghost:hover { background: var(--ghost-hover); }

        .footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--card-border);
            font-size: .82rem;
            color: var(--muted);
        }

        @keyframes rise {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: none; }
        }

        @media (max-width: 480px) {
            .error-card { padding: 2rem 1.25rem 1.5rem; border-radius: 20px; }
            .btn { width: 100%; justify-content: center; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>

<body>
    <main class="error-card" role="main">
        <div class="error-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">{!! $iconPaths !!}</svg>
        </div>

        <h1 class="error-code">{{ $code }}</h1>
        <h2 class="error-title">{{ $title }}</h2>
        <p class="error-message">{{ $message }}</p>
        <p class="error-description">{{ $description }}</p>

        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
            @if ($canReload)
                <button type="button" class="btn btn-ghost" onclick="location.reload()">Muat Ulang</button>
            @else
                <button type="button" class="btn btn-ghost" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">Halaman Sebelumnya</button>
            @endif
        </div>

        <footer class="footer">&copy; {{ date('Y') }} MARIMOI. Semua Hak Dilindungi.</footer>
    </main>
    <script>document.documentElement.dataset.accent = @json($accent);</script>
</body>

</html>
