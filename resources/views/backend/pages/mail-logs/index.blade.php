@extends('backend.partials.main', ['title' => 'Log Email'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-email-outline"></i></span>
            Log Email
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Log Email</li>
            </ul>
        </nav>
    </div>

    @unless ($mailerDelivers)
        <div class="alert alert-warning d-flex gap-2 align-items-start" role="alert">
            <i class="mdi mdi-alert"></i>
            <div>
                Mailer aktif <b>{{ $mailer }}</b>: email <b>tidak benar-benar dikirim</b> ke penerima (hanya dicatat).
                Untuk production, atur <code>MAIL_MAILER=smtp</code> beserta kredensial SMTP di <code>.env</code>.
            </div>
        </div>
    @endunless

    @if (($stats['queued'] ?? 0) > 0)
        <div class="alert alert-info d-flex gap-2 align-items-start" role="alert">
            <i class="mdi mdi-timer-sand"></i>
            <div>
                <b>{{ number_format($stats['queued']) }}</b> email masih menunggu di antrian. Bila angka ini terus bertambah,
                pastikan worker berjalan: <code>php artisan queue:work --tries=3</code>.
            </div>
        </div>
    @endif

    <div class="row g-3 stats-row-compact">
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Terkirim</p>
                            <h3 class="stat-value">{{ number_format($stats['sent']) }}</h3>
                        </div>
                        <i class="mdi mdi-email-check stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Gagal</p>
                            <h3 class="stat-value">{{ number_format($stats['failed']) }}</h3>
                        </div>
                        <i class="mdi mdi-email-alert stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Terkirim Hari Ini</p>
                            <h3 class="stat-value">{{ number_format($stats['sent_today']) }}</h3>
                        </div>
                        <i class="mdi mdi-calendar-today stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Dalam Antrian</p>
                            <h3 class="stat-value">{{ is_null($stats['queued']) ? '—' : number_format($stats['queued']) }}</h3>
                        </div>
                        <i class="mdi mdi-timer-sand stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h4 class="card-title mb-1">Riwayat Pengiriman</h4>
                            <p class="text-muted small mb-0">
                                Mailer: <b>{{ $mailer }}</b> · Antrian: <b>{{ $queueConnection }}</b> ·
                                Log disimpan {{ $retentionDays }} hari. Isi email tidak disimpan.
                            </p>
                        </div>
                        <form method="GET" action="{{ route('mail-logs.index') }}" class="d-flex gap-2" role="search">
                            @if ($status)
                                <input type="hidden" name="status" value="{{ $status }}">
                            @endif
                            <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm"
                                placeholder="Cari penerima / subjek" aria-label="Cari penerima atau subjek">
                            <button type="submit" class="btn btn-sm btn-gradient-primary"><i class="mdi mdi-magnify"></i></button>
                        </form>
                    </div>

                    <ul class="nav nav-pills mb-3 gap-1">
                        @foreach (['' => 'Semua'] + \App\Models\MailLog::STATUSES as $value => $label)
                            <li class="nav-item">
                                <a class="nav-link py-1 px-3 {{ ($status ?? '') === $value ? 'active' : '' }}"
                                    href="{{ route('mail-logs.index', array_filter(['status' => $value, 'q' => $search])) }}">
                                    {{ $label }}
                                    <span class="badge rounded-pill bg-light text-dark ms-1">
                                        {{ number_format($value === '' ? $stats['sent'] + $stats['failed'] : $stats[$value]) }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-dark" style="width: 150px;">Waktu</th>
                                    <th class="text-dark">Penerima</th>
                                    <th class="text-dark">Subjek</th>
                                    <th class="text-dark">Jenis</th>
                                    <th class="text-dark text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="small text-nowrap">
                                            {{ $log->created_at->translatedFormat('d M Y') }}<br>
                                            <span class="text-muted">{{ $log->created_at->format('H:i:s') }}</span>
                                        </td>
                                        <td class="small text-break">{{ $log->recipients ?: '-' }}</td>
                                        <td class="small">
                                            {{ $log->subject ?: '-' }}
                                            @if ($log->error)
                                                <details class="mt-1">
                                                    <summary class="text-danger">Lihat error</summary>
                                                    <pre class="small bg-light p-2 mb-0 text-wrap text-break">{{ $log->error }}</pre>
                                                </details>
                                            @endif
                                        </td>
                                        <td class="small">
                                            {{ $log->mailableLabel() }}
                                            @if ($log->mailer)
                                                <div class="text-muted">via {{ $log->mailer }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $log->status === \App\Models\MailLog::STATUS_SENT ? 'bg-success' : 'bg-danger' }}">
                                                {{ \App\Models\MailLog::STATUSES[$log->status] ?? $log->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            @if ($status || $search !== '')
                                                Tidak ada log email yang cocok dengan filter.
                                            @else
                                                Belum ada email yang tercatat. Log muncul setelah sistem mengirim email.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $logs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
