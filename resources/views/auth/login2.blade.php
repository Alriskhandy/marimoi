<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MARIMOI - Login</title>

    <!-- Favicons -->
    <link href="{{ asset('frontend/img/logo.svg') }}" rel="icon">
    <link href="{{ asset('frontend/img/logo.svg') }}" rel="apple-touch-icon">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background: url('frontend/img/hero2.png') no-repeat center center / cover">
    <!-- Main Container with Glass Effect -->
    <div class="w-full max-w-md">
        <!-- Logo/Brand Section -->
        <div class="text-center mb-8 ">
            <img src="{{ asset('frontend/img/logo_text.svg') }}" alt="Logo" class="h-20 mx-auto">
        </div>

        <!-- Login Card -->
        <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-2xl p-8 space-y-8 transition-all duration-500 hover:shadow-xl">
            <div class="text-center">
                <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-800 to-blue-600 bg-clip-text text-transparent">Selamat Datang</h2>
                <p class="text-gray-500 mt-2">Harap mengisi kredensial sebelum dapat masuk.</p>
            </div>

            <form class="space-y-6" action="{{ route('login') }}" method="POST">
                @csrf
                <!-- Email Input -->
                <div class="relative">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="email">
                        Email
                    </label>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                        <input 
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white/50"
                            type="email" 
                            id="email" name="email"
                            placeholder="Enter your email"
                            value="{{ old('email') }}" required autofocus autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password Input -->
                <div class="relative">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="password">
                        Password
                    </label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                        <input 
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white/50"
                            type="password" name="password"
                            id="password" 
                            placeholder="Enter your password"
                            required autocomplete="current-password"
                        >
                    </div>
                </div>

                <!-- Ingat Saya & Lupa Password -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label class="ml-2 text-gray-600 text-sm" for="remember">
                            Ingat Saya
                        </label>
                    </div>
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">Lupa Password?</a>
                </div>

                <!-- Tombol Masuk -->
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-700 to-cyan-600 text-white py-3 rounded-xl hover:opacity-90 transition duration-200 transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl"
                >
                    Masuk
                </button>
            </form>
            <div class="text-center">
                <p class="text-sm text-gray-500 mt-1">Bappeda Provinsi Maluku Utara</p>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>