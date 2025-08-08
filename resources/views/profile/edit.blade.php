@extends('backend.partials.main')
@push('styles')
    <style>
        /* Modern Profile Page Styles */

        /* Profile Header Card */
        .profile-header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 20px;
            color: white;
            overflow: hidden;
            position: relative;
        }

        .profile-header-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .profile-avatar-container {
            position: relative;
            display: inline-block;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.3);
            position: relative;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-placeholder {
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .profile-status-indicator {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            background: #4ade80;
            border: 3px solid white;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .profile-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .profile-email {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .profile-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .badge-success-custom,
        .badge-warning-custom,
        .badge-info-custom {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .profile-stats {
            display: flex;
            gap: 2rem;
            justify-content: flex-end;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
            color: white;
        }

        /* Modern Cards */
        .modern-card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
        }

        .security-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .card-header-modern {
            padding: 2rem 2rem 1rem;
            border-bottom: none;
            background: transparent;
        }

        .card-header-content {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
            flex-shrink: 0;
        }

        .security-card .card-icon.security-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 8px 24px rgba(255, 255, 255, 0.2);
        }

        .card-title-modern {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
            color: #1f2937;
        }

        .security-card .card-title-modern {
            color: white;
        }

        .card-subtitle-modern {
            color: #6b7280;
            margin: 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .security-card .card-subtitle-modern {
            color: rgba(255, 255, 255, 0.9);
        }

        .card-body-modern {
            padding: 1rem 2rem 2rem;
        }

        /* Modern Form Styles */
        .form-group-modern {
            margin-bottom: 2rem;
        }

        .form-label-modern {
            display: flex;
            align-items: center;
            font-weight: 600;
            font-size: 0.95rem;
            color: #374151;
            margin-bottom: 0.75rem;
        }

        .security-card .form-label-modern {
            color: white;
        }

        .required-asterisk {
            color: #ef4444;
            margin-left: 0.25rem;
        }

        .input-wrapper,
        .password-input-wrapper {
            position: relative;
        }

        .form-control-modern {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            background: #ffffff;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .security-card .form-control-modern {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .security-card .form-control-modern:focus {
            border-color: white;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
        }

        .input-focus-border {
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .form-control-modern:focus+.input-focus-border {
            width: 100%;
        }

        .password-input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .security-card .password-toggle {
            color: rgba(255, 255, 255, 0.8);
        }

        .security-card .password-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .password-requirements {
            margin-top: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .security-card .password-requirements {
            color: rgba(255, 255, 255, 0.8);
        }

        .error-message {
            display: flex;
            align-items: center;
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 8px;
            border-left: 4px solid #ef4444;
        }

        .verification-notice {
            margin-top: 0.75rem;
            padding: 1rem;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-radius: 12px;
            color: white;
        }

        .verification-content {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .verification-link {
            background: none;
            border: none;
            color: white;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 600;
        }

        .verification-link:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Modern Buttons */
        .form-actions {
            margin-top: 2rem;
            display: flex;
            justify-content: flex-end;
        }

        .btn-save-modern,
        .btn-update-modern {
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            min-width: 160px;
        }

        .btn-update-modern {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            width: 100%;
        }

        .security-card .btn-update-modern {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-save-modern:hover,
        .btn-update-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.3s ease;
        }

        .btn-loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .btn-save-modern.loading .btn-content,
        .btn-update-modern.loading .btn-content {
            opacity: 0;
        }

        .btn-save-modern.loading .btn-loader,
        .btn-update-modern.loading .btn-loader {
            opacity: 1;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
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

        /* Modern Alerts */
        .alert-modern {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: none;
            animation: slideInDown 0.4s ease;
        }

        .alert-success-modern {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .alert-info-modern {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }

        .alert-danger-modern {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .alert-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .alert-icon {
            font-size: 1.2rem;
            margin-right: 0.75rem;
        }

        .alert-message {
            flex: 1;
            font-weight: 500;
        }

        .alert-close {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .alert-close:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .profile-stats {
                gap: 1rem;
            }

            .stat-number {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 992px) {

            .col-lg-8,
            .col-lg-4 {
                margin-bottom: 2rem;
            }

            .profile-header-card .row {
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
                margin-top: 1rem;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 1.5rem;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
            }

            .profile-name {
                font-size: 1.5rem;
            }

            .card-header-modern {
                padding: 1.5rem 1.5rem 1rem;
            }

            .card-body-modern {
                padding: 1rem 1.5rem 1.5rem;
            }

            .card-header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .card-icon {
                align-self: center;
            }

            .profile-badges {
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .card-header-modern {
                padding: 1rem 1rem 0.5rem;
            }

            .card-body-modern {
                padding: 1rem;
            }

            .form-control-modern {
                padding: 0.875rem 1rem;
            }

            .btn-save-modern,
            .btn-update-modern {
                padding: 0.875rem 1.5rem;
                font-size: 0.95rem;
            }

            .profile-avatar {
                width: 80px;
                height: 80px;
            }

            .profile-name {
                font-size: 1.25rem;
            }

            .stat-item {
                margin-bottom: 1rem;
            }
        }

        /* Loading States */
        .btn-save-modern:disabled,
        .btn-update-modern:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Focus States for Accessibility */
        .form-control-modern:focus,
        .password-toggle:focus,
        .btn-save-modern:focus,
        .btn-update-modern:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a419a 100%);
        }

        /* Print Styles */
        @media print {
            .profile-header-card {
                background: #f8f9fa !important;
                color: #000 !important;
            }

            .modern-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }

            .btn-save-modern,
            .btn-update-modern,
            .password-toggle {
                display: none !important;
            }
        }


        /* Animation for card hover */
        .modern-card {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Smooth transitions for all interactive elements */
        * {
            transition: color 0.2s ease,
                background-color 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        /* Enhanced focus ring for better accessibility */
        .form-control-modern:focus-visible,
        .password-toggle:focus-visible,
        .btn-save-modern:focus-visible,
        .btn-update-modern:focus-visible,
        .verification-link:focus-visible {
            outline: 2px solid #667eea;
            outline-offset: 2px;
            border-radius: 8px;
        }

        /* Improved button states */
        .btn-save-modern:active,
        .btn-update-modern:active {
            transform: translateY(0);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        /* Enhanced form validation styles */
        .form-control-modern.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        .form-control-modern.is-invalid:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }
    </style>
@endpush
@section('main')
    <!-- Add CSRF token to meta for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary  me-2">
                <i class="mdi mdi-account-circle"></i>
            </span> Profile Settings
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Profile
                </li>
            </ul>
        </nav>
    </div>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <!-- Profile Header Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card profile-header-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="profile-avatar-container">
                                <div class="profile-avatar">
                                    @if (auth()->user()->profile_photo_path)
                                        <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}"
                                            alt="Profile Photo" class="profile-image">
                                    @else
                                        <div class="profile-placeholder">
                                            <i class="mdi mdi-account"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="profile-status-indicator"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4 class="profile-name">{{ auth()->user()->name }}</h4>
                            <p class="profile-email">{{ auth()->user()->email }}</p>
                            <div class="profile-badges">
                                @if (auth()->user()->email_verified_at)
                                    <span class="badge badge-success-custom">
                                        <i class="mdi mdi-check-circle me-1"></i>Verified
                                    </span>
                                @else
                                    <span class="badge badge-warning-custom">
                                        <i class="mdi mdi-alert-circle me-1"></i>Unverified
                                    </span>
                                @endif
                                <span class="badge badge-info-custom">
                                    <i class="mdi mdi-calendar me-1"></i>
                                    Member since {{ auth()->user()->created_at->format('M Y') }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-number">{{ auth()->user()->updated_at->format('d-M-Y') }}</span>
                                    <span class="stat-label">Last Update</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Update Profile Information -->
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card modern-card">
                <div class="card-header-modern">
                    <div class="card-header-content">
                        <div class="card-icon">
                            <i class="mdi mdi-account-edit"></i>
                        </div>
                        <div>
                            <h5 class="card-title-modern">Profile Information</h5>
                            <p class="card-subtitle-modern">Update your account's profile information and email address</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <form id="profileForm" method="POST" action="{{ route('profile.update') }}"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <!-- Name Field -->
                        <div class="form-group-modern">
                            <label for="name" class="form-label-modern">
                                <i class="mdi mdi-account me-2"></i>Full Name
                                <span class="required-asterisk">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="text" class="form-control-modern  @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required
                                    placeholder="Enter your full name">
                                <div class="input-focus-border"></div>
                            </div>
                            @error('name')
                                <div class="error-message">
                                    <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Email Field -->
                        <div class="form-group-modern">
                            <label for="email" class="form-label-modern">
                                <i class="mdi mdi-email me-2"></i>Email Address
                                <span class="required-asterisk">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="email" class="form-control-modern  @error('email') is-invalid @enderror"
                                    id="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                                    required placeholder="Enter your email address">
                                <div class="input-focus-border"></div>
                            </div>
                            @error('email')
                                <div class="error-message">
                                    <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror

                            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                                <div class="verification-notice">
                                    <div class="verification-content">
                                        <i class="mdi mdi-alert-circle-outline me-2"></i>
                                        <span>Your email address is unverified.</span>
                                        <button form="send-verification" type="submit" class="verification-link">
                                            Resend verification email
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-save-modern">
                                <span class="btn-content">
                                    <i class="mdi mdi-content-save me-2"></i>
                                    Save Changes
                                </span>
                                <div class="btn-loader">
                                    <div class="spinner"></div>
                                </div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Update Password -->
        <div class="col-lg-4 grid-margin stretch-card">
            <div class="card modern-card security-card">
                <div class="card-header-modern">
                    <div class="card-header-content">
                        <div class="card-icon security-icon">
                            <i class="mdi mdi-lock-reset"></i>
                        </div>
                        <div>
                            <h5 class="card-title-modern">Security</h5>
                            <p class="card-subtitle-modern">Update your password to keep your account secure</p>
                        </div>
                    </div>
                </div>
                <div class="card-body-modern">
                    <form id="passwordForm" method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('PUT')

                        <!-- Current Password -->
                        <div class="form-group-modern">
                            <label for="current_password" class="form-label-modern">
                                <i class="mdi mdi-lock me-2"></i>Current Password
                                <span class="required-asterisk">*</span>
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password"
                                    class="form-control-modern @error('current_password', 'updatePassword') is-invalid @enderror"
                                    id="current_password" name="current_password" required
                                    placeholder="Enter current password">
                                <button type="button" class="password-toggle"
                                    onclick="togglePassword('current_password')">
                                    <i class="text-dark mdi mdi-eye-outline" id="current_password_icon"></i>
                                </button>
                                <div class="input-focus-border"></div>
                            </div>
                            @error('current_password', 'updatePassword')
                                <div class="error-message">
                                    <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- New Password -->
                        <div class="form-group-modern">
                            <label for="password" class="form-label-modern">
                                <i class="mdi mdi-lock-plus me-2"></i>New Password
                                <span class="required-asterisk">*</span>
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password"
                                    class="form-control-modern @error('password', 'updatePassword') is-invalid @enderror"
                                    id="password" name="password" required placeholder="Enter new password">
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="mdi text-dark mdi-eye-outline" id="password_icon"></i>
                                </button>
                                <div class="input-focus-border"></div>
                            </div>
                            <div class="password-requirements">
                                <small>Minimum 8 characters required</small>
                            </div>
                            @error('password', 'updatePassword')
                                <div class="error-message">
                                    <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group-modern">
                            <label for="password_confirmation" class="form-label-modern">
                                <i class="mdi mdi-lock-check me-2"></i>Confirm Password
                                <span class="required-asterisk">*</span>
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password"
                                    class="form-control-modern @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                                    id="password_confirmation" name="password_confirmation" required
                                    placeholder="Confirm new password">
                                <button type="button" class="password-toggle"
                                    onclick="togglePassword('password_confirmation')">
                                    <i class="mdi text-dark mdi-eye-outline" id="password_confirmation_icon"></i>
                                </button>
                                <div class="input-focus-border"></div>
                            </div>
                            @error('password_confirmation', 'updatePassword')
                                <div class="error-message">
                                    <i class="mdi mdi-alert-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-update-modern">
                                <span class="btn-content">
                                    <i class="mdi mdi-shield-check me-2"></i>
                                    Update Password
                                </span>
                                <div class="btn-loader">
                                    <div class="spinner"></div>
                                </div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Verification Form (Hidden) -->
    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
        <form id="send-verification" method="POST" action="{{ route('verification.send') }}" style="display: none;">
            @csrf
        </form>
    @endif
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Show success messages
            @if (session('status'))
                showAlert('{{ session('status') }}', 'success');
            @endif

            @if (session('success'))
                showAlert('{{ session('success') }}', 'success');
            @endif

            // Add floating label animation
            $('.form-control-modern').on('focus blur', function() {
                const $this = $(this);
                const $wrapper = $this.closest('.input-wrapper, .password-input-wrapper');

                if ($this.val() !== '' || $this.is(':focus')) {
                    $wrapper.addClass('has-content');
                } else {
                    $wrapper.removeClass('has-content');
                }
            });

            // Check initial values
            $('.form-control-modern').each(function() {
                const $this = $(this);
                const $wrapper = $this.closest('.input-wrapper, .password-input-wrapper');

                if ($this.val() !== '') {
                    $wrapper.addClass('has-content');
                }
            });
        });

        // Helper function to show alerts
        function showAlert(message, type = 'success') {
            const alertClass = type === 'success' ? 'alert-success-modern' :
                type === 'info' ? 'alert-info-modern' : 'alert-danger-modern';
            const iconClass = type === 'success' ? 'mdi-check-circle' :
                type === 'info' ? 'mdi-information' : 'mdi-alert-circle';

            const alertHtml = `
                <div class="alert-modern ${alertClass}">
                    <div class="alert-content">
                        <i class="mdi ${iconClass} alert-icon"></i>
                        <span class="alert-message">${message}</span>
                        <button type="button" class="alert-close" onclick="this.parentElement.parentElement.remove()">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                </div>
            `;

            $('#alertContainer').html(alertHtml);

            // Auto hide after 5 seconds
            setTimeout(function() {
                $('.alert-modern').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('mdi-eye-outline');
                icon.classList.add('mdi-eye-off-outline');
            } else {
                field.type = 'password';
                icon.classList.remove('mdi-eye-off-outline');
                icon.classList.add('mdi-eye-outline');
            }
        }

        // Enhanced form submission with loading states
        $('#profileForm').on('submit', function(e) {
            const submitBtn = $(this).find('.btn-save-modern');
            submitBtn.addClass('loading');
        });

        $('#passwordForm').on('submit', function(e) {
            const submitBtn = $(this).find('.btn-update-modern');
            submitBtn.addClass('loading');
        });

        // Auto-hide alerts after email verification
        @if (session('status') === 'verification-link-sent')
            showAlert('A new verification link has been sent to your email address.', 'info');
        @endif
    </script>
@endsection
