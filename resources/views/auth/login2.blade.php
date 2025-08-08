<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MARIMOI - Login</title>

    <!-- Favicons -->
    <link href="{{ asset('frontend/favicon/favicon.ico') }}" rel="icon" type="image/webp">
    <link href="{{ asset('frontend/favicon/apple-touch-icon.png') }}" rel="apple-touch-icon">
    <link href="{{ asset('frontend/favicon/favicon-32x32.png') }}" rel="icon" sizes="32x32">
    <link href="{{ asset('frontend/favicon/favicon-16x16.png') }}" rel="icon" sizes="16x16">
    <link href="{{ asset('frontend/favicon/android-chrome-192x192.png') }}" rel="icon" sizes="192x192">
    <link href="{{ asset('frontend/favicon/android-chrome-512x512.png') }}" rel="icon" sizes="512x512">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Fontawesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <!-- Vendor CSS Files -->
    <link href="{{ asset('frontend/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">

    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>

    <!-- Main CSS File -->
    <link href="{{ asset('frontend/css/main.css') }}" rel="stylesheet">

    <style>
        :root {
            --accent-color: #6c63ff;
            --gradient-purple: linear-gradient(90deg, rgb(218, 140, 255), rgb(154, 85, 255));
            --heading-color: #2e2e4d;
            --default-color: #4b4b6a;
            --surface-color: #ffffff;
        }

        body {
            font-family: "Poppins", sans-serif;
            background: url('{{ asset('frontend/img/hero2.png') }}') no-repeat center center / cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(14, 29, 52, 0.9) 0%, rgba(14, 29, 52, 0.7) 100%);
            z-index: 1;
        }

        .login-container {
            position: relative;
            z-index: 2;
            max-width: 420px;
            width: 100%;
            margin: 15px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 8px;
        }

        .login-logo img {
            height: 60px;
            margin-bottom: 5px;
        }

        .login-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .login-title h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--heading-color);
            margin-bottom: 5px;
        }

        .login-title p {
            color: var(--default-color);
            font-size: 14px;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--heading-color);
            font-size: 13px;
        }

        .input-group {
            position: relative;
        }

        .input-group .form-control {
            width: 100%;
            padding: 12px 16px 12px 45px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .input-group .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
            background: #fff;
        }

        .input-group .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-color);
            font-size: 16px;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .form-check-input {
            margin-right: 10px;
            accent-color: var(--accent-color);
        }

        .form-check-label {
            font-size: 14px;
            color: var(--default-color);
        }

        .forgot-password {
            font-size: 14px;
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: color-mix(in srgb, var(--accent-color), transparent 20%);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background: var(--gradient-purple);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(108, 99, 255, 0.3);
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        .login-footer p {
            font-size: 13px;
            color: var(--default-color);
            opacity: 0.7;
            margin-bottom: 5px;
        }

        .form-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 20px 15px;
            }

            .login-title h2 {
                font-size: 22px;
            }
            
            .login-logo img {
                height: 50px;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
            
            .input-group .form-control {
                padding: 10px 15px 10px 40px;
                font-size: 14px;
            }
            
            .input-group .input-icon {
                font-size: 15px;
            }
            
            .login-btn {
                padding: 10px;
                font-size: 14px;
            }
        }
        
        @media (min-width: 992px) {
            .login-container {
                max-width: 380px;
            }
            
            .login-card {
                padding: 25px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card" data-aos="fade-up">
            <div class="login-logo">
                <img src="{{ asset('frontend/img/logo_text.svg') }}" alt="MARIMOI Logo">
            </div>

            <div class="login-title">
                <h2>Selamat Datang</h2>
                <p>Harap mengisi kredensial sebelum dapat masuk</p>
            </div>

            <form action="{{ route('login') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" class="form-control" id="email" name="email"
                            placeholder="Masukkan email Anda" required autofocus value="{{ old('email') }}"
                            autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Masukkan password Anda" required autocomplete="current-password">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Ingat Saya
                        </label>
                    </div>
                </div>

                <div class="form-row justify-content-center">
                    <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}">
                    </div>
                </div>
                @error('h-captcha-response')
                    <div class="invalid-feedback d-block text-center">
                        {{ $message }}
                    </div>
                @enderror

                <button type="submit" class="login-btn">
                    Masuk
                </button>
            </form>

            <div class="login-footer">
                <p class="mb-1">@2025 Bappeda Provinsi Maluku Utara</p>
                <p class="mb-0">Sistem Informasi MARIMOI</p>
            </div>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="{{ asset('frontend/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('frontend/vendor/aos/aos.js') }}"></script>
    <script src="{{ asset('frontend/js/main.js') }}"></script>

    <script>
        AOS.init({
            duration: 1000,
            easing: 'ease-in-out',
            once: true
        });
    </script>
</body>

</html>
