@extends('frontend.layouts.dark', ['title' => 'Survey Feedback - MARIMOI'])

@push('styles')
    <link href="{{ asset('frontend/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <!-- Tailwind CSS via Vite -->
    @vite(['resources/css/app.css'])
    <style>
        /* Typography Fonts */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
        }

        p,
        body,
        ul,
        li {
            font-family: 'Inter', sans-serif;
        }

        /* Star rating styles */
        .star-rating {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .star {
            font-size: 2.5rem;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .star.active {
            color: #f59e0b;
        }

        /* Form styling */
        .form-section {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .form-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .form-section-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .form-section-body {
            padding: 1.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background-color: #ffffff;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control.is-valid {
            border-color: #10b981;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
        }

        .input-group {
            position: relative;
            margin-bottom: 1rem;
        }

        .input-group-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            z-index: 10;
        }

        .form-control.has-icon {
            padding-left: 3rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-overlay.show .modal-content {
            transform: scale(1);
        }

        /* Character counter */
        .char-counter {
            font-size: 0.8rem;
            color: #6b7280;
            text-align: right;
            margin-top: 0.25rem;
        }

        .char-counter.warning {
            color: #f59e0b;
        }

        .char-counter.error {
            color: #ef4444;
        }

        /* Statistics cards */
        .stats-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .star {
                font-size: 2rem;
            }

            .form-section-body {
                padding: 1rem;
            }

            .stats-number {
                font-size: 2rem;
            }
        }

        .container {
            max-width: 1200px;
        }

        /* Loading animation */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('main')
    <!-- Survey Section -->
    <section class="min-h-auto mt-[76px] pt-0 pb-8 bg-slate-50">
        <!-- Section Title -->
        <div class="container mx-auto px-4 text-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold pt-8 text-slate-800 mb-4">
                Survey Feedback MARIMOI
            </h2>
            <p class="text-slate-600 text-sm md:text-base max-w-[800px] mx-auto mb-6 leading-relaxed">
                Berikan penilaian dan masukan Anda untuk membantu kami meningkatkan kualitas platform MARIMOI.
            </p>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 max-w-3xl mx-auto">
                <div class="stats-card">
                    <div class="stats-number text-blue-600">{{ \App\Models\Survey::count() ?? 127 }}</div>
                    <div class="stats-label">Total Feedback</div>
                </div>
                <div class="stats-card">
                    <div class="stats-number text-green-600">
                        {{ number_format(\App\Models\Survey::avg('rating') ?? 4.3, 1) }}</div>
                    <div class="stats-label">Rating Rata-rata</div>
                </div>
                <div class="stats-card">
                    <div class="stats-number text-purple-600">
                        {{ \App\Models\Survey::whereDate('created_at', today())->count() ?? 8 }}</div>
                    <div class="stats-label">Feedback Hari Ini</div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Main Form Column -->
                <div class="lg:col-span-2">
                    <form id="surveyForm" action="{{ route('survey.submit') }}" method="POST">
                        @csrf

                        <!-- Data Pribadi Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="flex items-center text-lg">
                                    <i class="bi bi-person-fill me-2"></i>
                                    Data Pribadi
                                </h3>
                            </div>
                            <div class="form-section-body">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="input-group">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Nama Lengkap <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <i class="bi bi-person input-group-icon"></i>
                                            <input type="text" name="name" id="name"
                                                class="form-control has-icon @error('name') is-invalid @enderror"
                                                placeholder="Masukkan nama lengkap Anda" value="{{ old('name') }}"
                                                required>
                                        </div>
                                        @error('name')
                                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="input-group">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <i class="bi bi-envelope input-group-icon"></i>
                                            <input type="email" name="email" id="email"
                                                class="form-control has-icon @error('email') is-invalid @enderror"
                                                placeholder="nama@email.com" value="{{ old('email') }}" required>
                                        </div>
                                        @error('email')
                                            <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="input-group">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Nomor Telepon
                                        </label>
                                        <div class="relative">
                                            <i class="bi bi-phone input-group-icon"></i>
                                            <input type="text" name="phone" id="phone"
                                                class="form-control has-icon" placeholder="08xxxxxxxxxx"
                                                value="{{ old('phone') }}">
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Organisasi/Instansi
                                        </label>
                                        <div class="relative">
                                            <i class="bi bi-building input-group-icon"></i>
                                            <input type="text" name="organization" id="organization"
                                                class="form-control has-icon" placeholder="Nama organisasi/instansi"
                                                value="{{ old('organization') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="input-group mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Posisi/Jabatan
                                    </label>
                                    <div class="relative">
                                        <i class="bi bi-briefcase input-group-icon"></i>
                                        <input type="text" name="position" id="position" class="form-control has-icon"
                                            placeholder="Jabatan atau posisi Anda" value="{{ old('position') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rating Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="flex items-center text-lg">
                                    <i class="bi bi-star-fill me-2"></i>
                                    Penilaian Platform MARIMOI
                                </h3>
                            </div>
                            <div class="form-section-body">
                                <div class="text-center mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-4">
                                        Bagaimana pengalaman Anda menggunakan platform MARIMOI? <span
                                            class="text-red-500">*</span>
                                    </label>
                                    <div class="star-rating">
                                        <span class="star" data-rating="1">★</span>
                                        <span class="star" data-rating="2">★</span>
                                        <span class="star" data-rating="3">★</span>
                                        <span class="star" data-rating="4">★</span>
                                        <span class="star" data-rating="5">★</span>
                                    </div>
                                    <input type="hidden" name="rating" id="rating" value="{{ old('rating') }}"
                                        required>
                                    <div class="text-sm text-gray-500 mt-2">
                                        <span id="rating-text">Silakan pilih rating Anda</span>
                                    </div>
                                    @error('rating')
                                        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Feedback Section -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="flex items-center text-lg">
                                    <i class="bi bi-chat-dots-fill me-2"></i>
                                    Feedback & Saran
                                </h3>
                            </div>
                            <div class="form-section-body">
                                <div class="input-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Apa yang Anda sukai dari platform MARIMOI?
                                    </label>
                                    <textarea name="feedback" id="feedback" class="form-control" rows="4"
                                        placeholder="Ceritakan pengalaman positif Anda dalam menggunakan platform MARIMOI...">{{ old('feedback') }}</textarea>
                                    <div class="char-counter" id="feedback-counter">0/1000</div>
                                </div>

                                <div class="input-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Saran untuk perbaikan platform
                                    </label>
                                    <textarea name="suggestions" id="suggestions" class="form-control" rows="4"
                                        placeholder="Bagaimana kami bisa meningkatkan platform MARIMOI untuk melayani Anda lebih baik?">{{ old('suggestions') }}</textarea>
                                    <div class="char-counter" id="suggestions-counter">0/1000</div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="form-section">
                            <div class="form-section-body text-center">
                                <!-- hCaptcha -->
                                <div class="mb-6">
                                    <div class="h-captcha mx-auto"
                                        data-sitekey="{{ config('services.hcaptcha.sitekey_test') }}"></div>
                                </div>

                                <button type="submit" class="btn-primary" id="submitBtn">
                                    <i class="bi bi-send-fill"></i>
                                    <span id="submitText">Kirim Feedback</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Guidelines -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="flex items-center text-lg">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Panduan Mengisi Survey
                            </h3>
                        </div>
                        <div class="form-section-body">
                            <div class="space-y-4 text-sm text-gray-600">
                                <div>
                                    <h4 class="font-semibold text-gray-800 mb-2">📝 Langkah-langkah:</h4>
                                    <ol class="list-decimal list-inside space-y-1 pl-2">
                                        <li>Isi data pribadi dengan lengkap</li>
                                        <li>Berikan rating pengalaman Anda</li>
                                        <li>Tuliskan feedback yang konstruktif</li>
                                        <li>Berikan saran perbaikan</li>
                                        <li>Verifikasi captcha dan kirim</li>
                                    </ol>
                                </div>

                                <div>
                                    <h4 class="font-semibold text-gray-800 mb-2">💡 Tips:</h4>
                                    <ul class="list-disc list-inside space-y-1 pl-2">
                                        <li>Berikan feedback yang spesifik</li>
                                        <li>Sertakan contoh jika memungkinkan</li>
                                        <li>Fokus pada aspek yang bisa diperbaiki</li>
                                        <li>Gunakan bahasa yang sopan dan konstruktif</li>
                                    </ul>
                                </div>

                                <div class="bg-blue-50 p-3 rounded-lg">
                                    <h4 class="font-semibold text-blue-800 mb-1">🔒 Privasi Terjamin</h4>
                                    <p class="text-blue-700 text-xs">
                                        Data Anda aman dan hanya digunakan untuk peningkatan layanan platform MARIMOI.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="flex items-center text-lg">
                                <i class="bi bi-telephone-fill me-2"></i>
                                Butuh Bantuan?
                            </h3>
                        </div>
                        <div class="form-section-body">
                            <div class="space-y-3 text-sm text-gray-600">
                                <div class="flex items-center">
                                    <i class="bi bi-envelope me-2 text-gray-400"></i>
                                    <span>bappeda.provmalut024@gmail.com</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="bi bi-globe me-2 text-gray-400"></i>
                                    <a href="https://bappeda.malutprov.go.id/" target="_blank"
                                        class="text-blue-600 hover:text-blue-800">
                                        bappeda.malutprov.go.id
                                    </a>
                                </div>
                                <div class="flex items-center">
                                    <i class="bi bi-building me-2 text-gray-400"></i>
                                    <span>Bappeda Provinsi Maluku Utara</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Stats -->
                    <div class="form-section">
                        <div class="form-section-header">
                            <h3 class="flex items-center text-lg">
                                <i class="bi bi-graph-up me-2"></i>
                                Statistik Terkini
                            </h3>
                        </div>
                        <div class="form-section-body">
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Rating 5 Bintang:</span>
                                    <span
                                        class="font-semibold text-yellow-600">{{ \App\Models\Survey::where('rating', 5)->count() ?? 45 }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Saran Diterima:</span>
                                    <span
                                        class="font-semibold text-blue-600">{{ \App\Models\Survey::whereNotNull('suggestions')->where('suggestions', '!=', '')->count() ?? 23 }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Feedback Bulan Ini:</span>
                                    <span
                                        class="font-semibold text-green-600">{{ \App\Models\Survey::whereMonth('created_at', date('m'))->count() ?? 34 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Success Modal -->
    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <div class="mb-4">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-content-center">
                    <i class="bi bi-check-circle-fill text-green-500 text-3xl"></i>
                </div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Terima Kasih!</h3>
            <p class="text-gray-600 mb-4">
                Feedback Anda telah berhasil dikirim dan sangat berharga bagi pengembangan platform MARIMOI.
            </p>
            <button onclick="closeModal()" class="btn-primary">
                <i class="bi bi-check"></i>
                Tutup
            </button>
        </div>
    </div>

    <!-- Footer Section -->
    @include('frontend.partials.footer-dark-tailwind')
@endsection

@push('scripts')
    <!-- Vite JavaScript -->
    @vite(['resources/js/app.js'])
    <!-- hCaptcha -->
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star');
            const ratingInput = document.getElementById('rating');
            const ratingText = document.getElementById('rating-text');
            const form = document.getElementById('surveyForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const successModal = document.getElementById('successModal');

            // Configuration
            const CONFIG = {
                maxCharacters: 1000,
                autoSaveDelay: 1500,
                validationDelay: 300
            };

            // State management
            let formState = {
                isSubmitting: false,
                autoSaveTimeout: null,
                validationTimeout: null,
                currentRating: 0
            };

            const ratingTexts = {
                1: 'Sangat Tidak Puas',
                2: 'Tidak Puas',
                3: 'Cukup',
                4: 'Puas',
                5: 'Sangat Puas'
            };

            // Initialize all functionality
            initializeStarRating();
            initializeCharacterCounters();
            initializeFormValidation();
            initializeFormSubmission();
            initializeAutoSave();
            initializeEnhancedFeatures();
            restoreFormData();

            // Star rating functionality
            function initializeStarRating() {
                stars.forEach(star => {
                    star.addEventListener('click', function() {
                        const rating = parseInt(this.dataset.rating);
                        setRating(rating);
                        triggerAutoSave('rating', rating);
                    });

                    star.addEventListener('mouseover', function() {
                        const rating = parseInt(this.dataset.rating);
                        highlightStars(rating);
                        updateRatingText(rating);
                    });
                });

                const starContainer = document.querySelector('.star-rating');
                if (starContainer) {
                    starContainer.addEventListener('mouseleave', function() {
                        const currentRating = formState.currentRating;
                        if (currentRating) {
                            highlightStars(currentRating);
                            updateRatingText(currentRating);
                        } else {
                            clearStars();
                            resetRatingText();
                        }
                    });
                }
            }

            function setRating(rating) {
                formState.currentRating = rating;
                ratingInput.value = rating;
                highlightStars(rating);
                updateRatingText(rating);

                // Clear any error styling
                if (ratingText) {
                    ratingText.style.color = '';
                }
            }

            function highlightStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }

            function clearStars() {
                stars.forEach(star => {
                    star.classList.remove('active');
                });
            }

            function updateRatingText(rating) {
                if (ratingText) {
                    ratingText.textContent = ratingTexts[rating] || 'Silakan pilih rating Anda';
                }
            }

            function resetRatingText() {
                if (ratingText) {
                    ratingText.textContent = 'Silakan pilih rating Anda';
                }
            }

            // Character counter
            function initializeCharacterCounters() {
                setupCharCounter('feedback', 'feedback-counter');
                setupCharCounter('suggestions', 'suggestions-counter');
            }

            function setupCharCounter(textareaId, counterId, maxLength = CONFIG.maxCharacters) {
                const textarea = document.getElementById(textareaId);
                const counter = document.getElementById(counterId);

                if (textarea && counter) {
                    textarea.addEventListener('input', function() {
                        updateCharCounter(this, counter, maxLength);
                        triggerAutoSave(textareaId, this.value);
                    });

                    // Initial count
                    updateCharCounter(textarea, counter, maxLength);
                }
            }

            function updateCharCounter(textarea, counter, maxLength) {
                const length = textarea.value.length;
                counter.textContent = `${length}/${maxLength}`;

                // Reset classes
                counter.classList.remove('warning', 'error');
                textarea.classList.remove('is-invalid');

                if (length > maxLength) {
                    counter.classList.add('error');
                    textarea.classList.add('is-invalid');
                } else if (length > maxLength * 0.9) {
                    counter.classList.add('warning');
                }
            }

            // Form validation
            function initializeFormValidation() {
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    field.addEventListener('blur', function() {
                        debounceValidation(this);
                    });

                    field.addEventListener('input', function() {
                        if (this.classList.contains('is-invalid')) {
                            debounceValidation(this);
                        }

                        // Auto-save for form fields
                        if (this.id) {
                            triggerAutoSave(this.id, this.value);
                        }
                    });
                });
            }

            function debounceValidation(field) {
                clearTimeout(formState.validationTimeout);
                formState.validationTimeout = setTimeout(() => {
                    validateField(field);
                }, CONFIG.validationDelay);
            }

            function validateField(field) {
                let isValid = true;

                // Email validation
                if (field.type === 'email' && field.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(field.value)) {
                        isValid = false;
                    }
                }

                // Phone validation
                if (field.name === 'phone' && field.value) {
                    const phoneRegex = /^[\d\s\-\+\(\)]+$/;
                    if (!phoneRegex.test(field.value)) {
                        isValid = false;
                    }
                }

                // Required field validation
                if (field.hasAttribute('required') && !field.value.trim()) {
                    isValid = false;
                }

                // Update field styling
                if (isValid) {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                } else {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                }

                return isValid;
            }

            function validateAllFields() {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });

                // Validate rating
                if (!ratingInput.value) {
                    isValid = false;
                    if (ratingText) {
                        ratingText.textContent = 'Rating wajib dipilih';
                        ratingText.style.color = '#ef4444';
                    }
                }

                return isValid;
            }

            // Auto-save functionality
            function initializeAutoSave() {
                // Auto-save is triggered in input event listeners
            }

            function triggerAutoSave(fieldName, value) {
                clearTimeout(formState.autoSaveTimeout);
                formState.autoSaveTimeout = setTimeout(() => {
                    saveToLocalStorage(fieldName, value);
                }, CONFIG.autoSaveDelay);
            }

            function saveToLocalStorage(fieldName, value) {
                try {
                    localStorage.setItem(`survey_${fieldName}`, value);
                } catch (error) {
                    console.warn('Failed to save to localStorage:', error);
                }
            }

            function restoreFormData() {
                const autoSaveFields = ['name', 'email', 'phone', 'organization', 'position', 'feedback',
                    'suggestions'
                ];

                autoSaveFields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field && !field.value) {
                        const savedValue = getFromLocalStorage(`survey_${fieldName}`);
                        if (savedValue) {
                            field.value = savedValue;
                            field.dispatchEvent(new Event('input'));
                        }
                    }
                });

                // Restore rating
                const savedRating = getFromLocalStorage('survey_rating');
                if (savedRating && !ratingInput.value) {
                    setRating(parseInt(savedRating));
                }
            }

            function getFromLocalStorage(key) {
                try {
                    return localStorage.getItem(key);
                } catch (error) {
                    console.warn('Failed to read from localStorage:', error);
                    return null;
                }
            }

            function clearAutoSavedData() {
                const autoSaveFields = ['name', 'email', 'phone', 'organization', 'position', 'feedback',
                    'suggestions', 'rating'
                ];
                autoSaveFields.forEach(fieldName => {
                    try {
                        localStorage.removeItem(`survey_${fieldName}`);
                    } catch (error) {
                        console.warn('Failed to clear localStorage:', error);
                    }
                });
            }

            // Form submission
            function initializeFormSubmission() {
                form.addEventListener('submit', handleFormSubmit);
            }

            function handleFormSubmit(e) {
                e.preventDefault();

                if (formState.isSubmitting) {
                    return;
                }

                // Validate all fields
                if (!validateAllFields()) {
                    scrollToFirstError();
                    return;
                }

                submitForm();
            }

            function scrollToFirstError() {
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstError.focus();
                }
            }

            function submitForm() {
                formState.isSubmitting = true;
                showLoadingState();

                // Get form data
                const formData = new FormData(form);

                // Add CSRF token if available
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    formData.append('_token', csrfToken.getAttribute('content'));
                }

                // Actual form submission
                fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            handleSubmissionSuccess();
                        } else {
                            throw new Error(data.message || 'Terjadi kesalahan saat mengirim feedback');
                        }
                    })
                    .catch(error => {
                        handleSubmissionError(error.message);
                    })
                    .finally(() => {
                        formState.isSubmitting = false;
                        resetButtonState();
                    });
            }

            function handleSubmissionSuccess() {
                showSuccessModal();
                resetForm();
                clearAutoSavedData();
                markSurveyCompleted();
            }

            function handleSubmissionError(message) {
                showErrorAlert(message);
            }

            function showLoadingState() {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<div class="loading-spinner"></div>';
                }
            }

            function resetButtonState() {
                if (submitBtn && submitText) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML =
                        '<i class="bi bi-send-fill"></i><span id="submitText">Kirim Feedback</span>';
                }
            }

            function showSuccessModal() {
                if (successModal) {
                    successModal.classList.add('show');
                }
            }

            function showErrorAlert(message) {
                alert('Error: ' + message);
            }

            function resetForm() {
                form.reset();
                formState.currentRating = 0;
                clearStars();
                resetRatingText();
                ratingInput.value = '';

                // Reset validation classes
                form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                    el.classList.remove('is-valid', 'is-invalid');
                });

                // Reset character counters
                const feedbackCounter = document.getElementById('feedback-counter');
                const suggestionsCounter = document.getElementById('suggestions-counter');

                if (feedbackCounter) {
                    feedbackCounter.textContent = '0/1000';
                    feedbackCounter.classList.remove('warning', 'error');
                }

                if (suggestionsCounter) {
                    suggestionsCounter.textContent = '0/1000';
                    suggestionsCounter.classList.remove('warning', 'error');
                }

                // Reset hCaptcha
                if (window.hcaptcha) {
                    try {
                        hcaptcha.reset();
                    } catch (error) {
                        console.warn('hCaptcha reset failed:', error);
                    }
                }
            }

            function markSurveyCompleted() {
                try {
                    localStorage.setItem('survey_completed_' + new Date().toDateString(), 'true');
                } catch (error) {
                    console.warn('Failed to mark survey as completed:', error);
                }
            }

            // Enhanced features
            function initializeEnhancedFeatures() {
                initializeFormInteractions();
                initializeKeyboardNavigation();
                initializeProgressTracking();
                initializeModalHandling();
                initializeAutoResize();
            }

            function initializeFormInteractions() {
                const formControls = form.querySelectorAll('.form-control');

                formControls.forEach(control => {
                    control.addEventListener('focus', function() {
                        const section = this.closest('.form-section');
                        if (section) {
                            section.style.transform = 'translateY(-2px)';
                            section.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.15)';
                        }
                    });

                    control.addEventListener('blur', function() {
                        const section = this.closest('.form-section');
                        if (section) {
                            section.style.transform = '';
                            section.style.boxShadow = '';
                        }
                    });
                });

                // Dynamic placeholder for email based on name
                const nameField = document.getElementById('name');
                const emailField = document.getElementById('email');

                if (nameField && emailField) {
                    nameField.addEventListener('input', function() {
                        if (this.value.length > 0 && !emailField.value) {
                            const firstName = this.value.split(' ')[0].toLowerCase();
                            emailField.placeholder = `${firstName}@email.com`;
                        }
                    });
                }
            }

            function initializeKeyboardNavigation() {
                form.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA' && event.target
                        .type !== 'submit') {
                        event.preventDefault();
                        const formElements = Array.from(form.elements);
                        const currentIndex = formElements.indexOf(event.target);
                        const nextElement = formElements[currentIndex + 1];

                        if (nextElement && nextElement.type !== 'submit') {
                            nextElement.focus();
                        }
                    }

                    if (event.key === 'Escape' && successModal && successModal.classList.contains('show')) {
                        closeModal();
                    }
                });
            }

            function initializeProgressTracking() {
                form.addEventListener('input', updateProgress);
                form.addEventListener('change', updateProgress);
                updateProgress(); // Initial check
            }

            function updateProgress() {
                const requiredFields = form.querySelectorAll('[required]');
                const filledFields = Array.from(requiredFields).filter(field => {
                    if (field.id === 'rating') {
                        return ratingInput.value;
                    }
                    return field.value.trim() !== '';
                });

                const progress = (filledFields.length / requiredFields.length) * 100;
                updateSubmitButtonAppearance(progress);
            }

            function updateSubmitButtonAppearance(progress) {
                if (!submitBtn || !submitText) return;

                if (progress === 100) {
                    submitBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    submitText.textContent = 'Siap Kirim Feedback';
                } else {
                    submitBtn.style.background = 'linear-gradient(135deg, #3b82f6 0%, #1e40af 100%)';
                    submitText.textContent = 'Kirim Feedback';
                }
            }

            function initializeModalHandling() {
                if (successModal) {
                    successModal.addEventListener('click', function(event) {
                        if (event.target === this) {
                            closeModal();
                        }
                    });
                }
            }

            function initializeAutoResize() {
                const textareas = form.querySelectorAll('textarea');
                textareas.forEach(textarea => {
                    textarea.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = this.scrollHeight + 'px';
                    });
                });
            }

            // Public functions for modal control
            window.closeModal = function() {
                if (successModal) {
                    successModal.classList.remove('show');
                }
                clearAutoSavedData();
            };

            // Initialize on page load
            console.log('Survey form initialized successfully');
        });
    </script>
@endpush
