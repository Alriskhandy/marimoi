<footer class="bg-white text-black pt-10 font-inter">
    <div class="max-w-[1400px] mx-auto px-8 grid grid-cols-1 lg:grid-cols-[3fr_3fr_1fr] gap-10 mb-10">

        <!-- Video Section -->
        <div class="footer-video-section">
            <iframe src="https://www.youtube-nocookie.com/embed/EQbw-E1ecB8" title="YouTube video player"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen referrerpolicy="strict-origin-when-cross-origin" loading="lazy"
                class="w-full h-full aspect-[2/1] border border-black/10 rounded-[5%]">
            </iframe>
        </div>

        <!-- Logo and Description Section -->
        <div class="pr-0 lg:pr-5">
            <div class="flex items-center mb-5">
                <img src="{{ asset('frontend/img/logo/logo-dark.png') }}" alt="MARIMOI Logo" class="w-auto h-10 mr-3"
                    loading="lazy">
                <span class="text-2xl font-bold text-black">MARIMOI</span>
            </div>

            <p class="text-[#0d0d0d] leading-relaxed mb-8 text-[0.95rem]">
                Sistem Informasi Manajemen Akselerasi Infrastruktur
                untuk Monitoring dan Integrasi Wilayah Provinsi Maluku
                Utara.
            </p>

            <div class="footer-address">
                <p class="text-[#0e0e0e] leading-normal text-sm">
                    Jl. Raya Lintas Halmahera, Sofifi<br>Maluku Utara
                </p>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="footer-contact-section">
            <h3 class="text-black mb-5 text-lg font-semibold">Bappeda Provinsi Maluku Utara</h3>

            <div class="flex items-center gap-2.5 mb-3 text-[#0e0e0e] text-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"
                    class="text-[var(--accent-color)] shrink-0">
                    <path
                        d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                </svg>
                <span>bappeda.provmalut024@gmail.com</span>
            </div>

            <div class="flex items-center gap-2.5 mb-3 text-[#0e0e0e] text-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"
                    class="text-[var(--accent-color)] shrink-0">
                    <path
                        d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
                </svg>
                <span>bappeda.malutprov.go.id</span>
            </div>

            <!-- Social Media Icons -->
            <div class="flex gap-3 mt-5">
                <a href="#"
                    class="bg-black/10 text-black w-9 h-9 rounded-md flex items-center justify-center no-underline transition-all duration-300 border border-white/20 hover:bg-[var(--bg-secondary)] hover:text-white hover:-translate-y-0.5">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z" />
                    </svg>
                </a>

                <a href="#"
                    class="bg-black/10 text-black w-9 h-9 rounded-md flex items-center justify-center no-underline transition-all duration-300 border border-white/20 hover:bg-[var(--bg-secondary)] hover:text-white hover:-translate-y-0.5">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.219-.359-1.219c0-1.142.662-1.995 1.482-1.995.699 0 1.037.219 1.037 1.142 0 .696-.219 1.738-.359 2.699-.199.937.699 1.699 2.061 1.699 2.466 0 4.128-3.075 4.128-6.7 0-2.76-1.855-4.827-5.226-4.827-3.794 0-6.177 2.774-6.177 5.875 0 1.07.262 1.826.698 2.403.199.241.219.359.159.657-.041.219-.159.657-.199.837-.041.298-.199.359-.462.219-1.657-.716-2.466-2.774-2.466-5.032 0-3.794 3.197-7.894 9.56-7.894 5.027 0 8.342 3.598 8.342 7.418 0 5.072-2.787 8.898-6.898 8.898-1.381 0-2.679-.758-3.118-1.699 0 0-.739 2.997-.898 3.654-.301 1.142-1.142 2.563-1.699 3.437C9.177 23.812 10.561 24 12.017 24c6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z" />
                    </svg>
                </a>

                <a href="#"
                    class="bg-black/10 text-black w-9 h-9 rounded-md flex items-center justify-center no-underline transition-all duration-300 border border-white/20 hover:bg-[var(--bg-secondary)] hover:text-white hover:-translate-y-0.5">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                    </svg>
                </a>
            </div>

            <div class="flex items-center gap-2.5 mt-1.5 text-black text-sm">
                <span>
                    <a href="{{ route('kebijakan_privasi') }}" class="no-underline text-inherit">Kebijakan Privasi</a> |
                    <a href="{{  route('syarat_ketentuan') }}" class="no-underline text-inherit">Syarat & Ketentuan</a>
                </span>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="border-t-[1.5px] border-black/30 py-4">
        <div class="max-w-[1200px] mx-auto px-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-[#222222] text-sm m-0">
                &copy; 2025 MARIMOI - Bappeda Provinsi Maluku Utara. All rights reserved.
            </p>
            <div class="text-center md:text-right">
                <span class="text-xs mr-1">Developed by</span>
                <span class="text-xs">
                    <a href="https://www.instagram.com/heartware_digital?igsh=MWdoM3A1a3p1bXFkMg%3D%3D&utm_source=qr"
                        target="_blank" rel="noopener noreferrer"
                        class="no-underline inline-flex items-center text-[#222] font-semibold">
                        Heartware Digital
                        <img src="{{ asset('frontend/img/logo_heartware.png') }}" alt="Heartware Digital Logo"
                            class="ml-1 h-[15px] w-auto">
                    </a>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Action Buttons -->
<div
    class="floating-buttons fixed bottom-5 right-5 flex flex-col gap-3 z-[1000] opacity-0 invisible translate-y-5 transition-all duration-300">
    <!-- Back to Top Button -->
    <button
        class="float-btn w-12 h-12 rounded-full border-0 cursor-pointer flex items-center justify-center shadow-[0_3px_10px_rgba(0,0,0,0.15)] transition-all duration-300 text-[0] relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 text-white hover:-translate-y-0.5 hover:shadow-[0_5px_15px_rgba(0,0,0,0.25)] active:translate-y-0 active:transition-transform active:duration-100"
        onclick="scrollToTop()" title="Kembali ke Atas">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
            class="transition-transform duration-300 hover:scale-110 hover:-translate-y-0.5">
            <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z" />
        </svg>
    </button>

    <!-- FAQ Button -->
    <a href="/faq"
        class="float-btn w-12 h-12 rounded-full border-0 cursor-pointer flex items-center justify-center shadow-[0_3px_10px_rgba(0,0,0,0.15)] transition-all duration-300 text-[0] relative overflow-hidden bg-gradient-to-br from-[var(--bg-primary)] to-[var(--bg-secondary)] text-white hover:-translate-y-0.5 hover:shadow-[0_5px_15px_rgba(0,0,0,0.25)] active:translate-y-0 active:transition-transform active:duration-100"
        title="FAQ - Pertanyaan yang Sering Diajukan">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"
            class="transition-transform duration-300 hover:scale-110">
            <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z" />
        </svg>
    </a>
</div>

@push('scripts')
    <script>
        // Back to Top functionality
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show/hide back to top button based on scroll position
        window.addEventListener('scroll', function() {
            const floatingButtons = document.querySelector('.floating-buttons');

            if (window.pageYOffset > 300) {
                floatingButtons.classList.remove('opacity-0', 'invisible', 'translate-y-5');
                floatingButtons.classList.add('opacity-100', 'visible', 'translate-y-0');
            } else {
                floatingButtons.classList.add('opacity-0', 'invisible', 'translate-y-5');
                floatingButtons.classList.remove('opacity-100', 'visible', 'translate-y-0');
            }
        });

        // Hide buttons initially
        document.addEventListener('DOMContentLoaded', function() {
            const floatingButtons = document.querySelector('.floating-buttons');
            floatingButtons.classList.add('opacity-0', 'invisible', 'translate-y-5');
        });
    </script>
@endpush
@push('styles')    
    <style>
        /* Custom CSS for elements that need CSS variables */
        .floating-buttons .float-btn:nth-child(1) {
            animation: fadeInUp 0.3s ease 0.1s both;
        }
    
        .floating-buttons .float-btn:nth-child(2) {
            animation: fadeInUp 0.3s ease 0.2s both;
        }
    
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
    
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .floating-buttons {
                bottom: 15px;
                right: 15px;
                gap: 10px;
            }
    
            .float-btn {
                width: 50px;
                height: 50px;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            }
    
            .float-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            }
    
            .float-btn svg {
                width: 18px;
                height: 18px;
            }
        }
    
        @media (max-width: 480px) {
            .floating-buttons {
                bottom: 12px;
                right: 12px;
                gap: 8px;
            }
    
            .float-btn {
                width: 45px;
                height: 45px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            }
    
            .float-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 3px 12px rgba(0, 0, 0, 0.3);
            }
    
            .float-btn svg {
                width: 16px;
                height: 16px;
            }
        }
    
        @media (max-width: 360px) {
            .floating-buttons {
                bottom: 10px;
                right: 10px;
                gap: 6px;
            }
    
            .float-btn {
                width: 42px;
                height: 42px;
            }
    
            .float-btn svg {
                width: 15px;
                height: 15px;
            }
        }
    </style>
@endpush
