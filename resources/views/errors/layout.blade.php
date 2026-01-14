<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {{ $code ?? 'Error' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }



        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            33% {
                transform: translateY(-20px) rotate(1deg);
            }

            66% {
                transform: translateY(-10px) rotate(-1deg);
            }
        }

        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 2rem;
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .error-code {
            font-size: clamp(4rem, 15vw, 10rem);
            font-weight: 900;
            background: linear-gradient(45deg, #3b82f6, #06b6d4, #3b82f6);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradient 3s ease infinite;
            letter-spacing: 0.1em;
            line-height: 1;
            margin-bottom: 1rem;
            text-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .error-message {
            font-size: clamp(1.2rem, 4vw, 1.8rem);
            font-weight: 600;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.4;
        }

        .error-description {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }



        .back-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .back-button:hover::before {
            left: 100%;
        }

        .back-button:active {
            transform: translateY(0);
        }

        .footer {
            margin-top: 3rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 400;
        }

        /* Floating elements */
        .floating-element {
            position: absolute;
            opacity: 0.1;
            animation: floatUpDown 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: -2s;
        }

        .floating-element:nth-child(2) {
            top: 20%;
            right: 10%;
            animation-delay: -4s;
        }

        .floating-element:nth-child(3) {
            bottom: 10%;
            left: 15%;
            animation-delay: -1s;
        }

        @keyframes floatUpDown {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .error-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .error-message {
                margin-bottom: 1.5rem;
            }

            .back-button {
                padding: 0.8rem 1.5rem;
                font-size: 1rem;
            }
        }

        /* Accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0a0a0a 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                radial-gradient(circle at 20% 80%, rgba(30, 64, 175, 0.25) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(3, 105, 161, 0.25) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.15) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            color: #ffffff;
            background: linear-gradient(45deg, #0f172a, #1e293b, #334155);
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.8);
            border-color: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body>
    <!-- Floating decorative elements -->
    <div class="floating-element"
        style="width: 50px; height: 50px; background: rgba(59, 130, 246, 0.1); border-radius: 50%;"></div>
    <div class="floating-element"
        style="width: 30px; height: 30px; background: rgba(37, 99, 235, 0.15); border-radius: 50%;"></div>
    <div class="floating-element"
        style="width: 40px; height: 40px; background: rgba(29, 78, 216, 0.12); border-radius: 50%;"></div>

    <div class="error-container">
        <h1 class="error-code">{{ $code ?? '404' }}</h1>

        <p class="error-message">
            {{ $message ?? 'Halaman yang Anda cari tidak ditemukan' }}
        </p>

        <p class="error-description">
            Sepertinya ada yang salah! Jangan khawatir, mari kita bawa Anda kembali ke jalur yang benar.
        </p>

        <a href="{{ url('/') }}" class="back-button">
            <span>←</span>
            Kembali ke Beranda
        </a>

        <footer class="footer">
            &copy; {{ date('Y') }} MARIMOI. Semua Hak Dilindungi.
        </footer>
    </div>
</body>

</html>
