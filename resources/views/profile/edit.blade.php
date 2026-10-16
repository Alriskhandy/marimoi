@extends('backend.partials.main', ['title' => 'Profile'])

@section('main')
    @php
        $profileUser = auth()->user();
        $mustVerify =
            $profileUser instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$profileUser->hasVerifiedEmail();
        $profilePhoto = $profileUser->profile_photo_path
            ? asset('storage/' . $profileUser->profile_photo_path)
            : asset('backend/assets/images/faces/profile.png');
    @endphp

    <div class="page-heading">
        <div class="page-heading-copy">
            <span class="page-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
            <div>
                <p class="eyebrow mb-1">Akun</p>
                <h1 class="h3 mb-1">Profile</h1>
                <p class="text-muted mb-0">Kelola informasi akun dan keamanan kata sandi Anda.</p>
            </div>
        </div>
    </div>

    @if (session('status') === 'profile-updated' || session('status') === 'password-updated' || session('success'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('success') ?? (session('status') === 'password-updated' ? 'Kata sandi berhasil diperbarui.' : 'Profil berhasil diperbarui.') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @elseif (session('status') === 'verification-link-sent')
        <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
            Tautan verifikasi baru telah dikirim ke email Anda.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <section class="row g-3 mt-1">
        <div class="col-12 col-xl-4">
            <div class="panel h-100 text-center profile-card">
                {{-- <img class="avatar-img avatar-xl profile-photo" src="{{ $profilePhoto }}" alt="{{ $profileUser->name }}"> --}}
                <h2 class="h5 mt-3 mb-1">{{ $profileUser->name }}</h2>
                <p class="text-muted mb-3">{{ $profileUser->role?->name ?? 'Pengguna' }}</p>
                <div class="d-flex justify-content-center gap-2">
                    @if ($profileUser->email_verified_at)
                        <span class="badge text-bg-success">Terverifikasi</span>
                    @else
                        <span class="badge text-bg-warning">Belum terverifikasi</span>
                    @endif
                </div>
                <div class="info-list mt-4 text-start">
                    <div><span>Email</span><strong>{{ $profileUser->email }}</strong></div>
                    @if ($profileUser->opd)
                        <div><span>OPD</span><strong>{{ $profileUser->opd->singkatan }}</strong></div>
                    @endif
                    <div><span>Bergabung</span><strong>{{ $profileUser->created_at->format('d M Y') }}</strong></div>
                    <div><span>Terakhir diperbarui</span><strong>{{ $profileUser->updated_at->format('d M Y') }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8 d-flex flex-column gap-3">
            <form id="profileForm" class="panel needs-validation" method="POST" action="{{ route('profile.update') }}"
                enctype="multipart/form-data" novalidate>
                @csrf
                @method('PATCH')
                <div class="panel-header">
                    <div>
                        <h2 class="h5 mb-1 section-title"><i class="bi bi-person-gear"
                                aria-hidden="true"></i><span>Informasi Profil</span></h2>
                        <p class="text-muted mb-0">Perbarui nama dan alamat email akun Anda.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nama Lengkap</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                            type="text" value="{{ old('name', $profileUser->name) }}" required>
                        <div class="invalid-feedback">{{ $errors->first('name') ?: 'Nama wajib diisi.' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                            type="email" value="{{ old('email', $profileUser->email) }}" required>
                        <div class="invalid-feedback">{{ $errors->first('email') ?: 'Masukkan email yang valid.' }}</div>
                    </div>
                    @if ($mustVerify)
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                Email Anda belum terverifikasi.
                                <button form="send-verification" type="submit"
                                    class="btn btn-link p-0 align-baseline">Kirim ulang email verifikasi</button>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle" aria-hidden="true"></i>
                        Simpan Perubahan</button>
                </div>
            </form>

            <form id="passwordForm" class="panel needs-validation" method="POST" action="{{ route('password.update') }}"
                novalidate>
                @csrf
                @method('PUT')
                <div class="panel-header">
                    <div>
                        <h2 class="h5 mb-1 section-title"><i class="bi bi-shield-lock"
                                aria-hidden="true"></i><span>Keamanan</span></h2>
                        <p class="text-muted mb-0">Gunakan kata sandi yang kuat, minimal 8 karakter.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="current_password">Kata Sandi Saat Ini</label>
                        <input class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                            id="current_password" name="current_password" type="password" autocomplete="current-password"
                            required>
                        <div class="invalid-feedback">
                            {{ $errors->updatePassword->first('current_password') ?: 'Wajib diisi.' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Kata Sandi Baru</label>
                        <input class="form-control @error('password', 'updatePassword') is-invalid @enderror" id="password"
                            name="password" type="password" autocomplete="new-password" minlength="8" required>
                        <div class="invalid-feedback">
                            {{ $errors->updatePassword->first('password') ?: 'Minimal 8 karakter.' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi</label>
                        <input class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                            id="password_confirmation" name="password_confirmation" type="password"
                            autocomplete="new-password" required>
                        <div class="invalid-feedback">
                            {{ $errors->updatePassword->first('password_confirmation') ?: 'Wajib diisi.' }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-shield-check" aria-hidden="true"></i>
                        Perbarui Kata Sandi</button>
                </div>
            </form>
        </div>
    </section>

    @if ($mustVerify)
        <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="d-none">
            @csrf
        </form>
    @endif
@endsection
