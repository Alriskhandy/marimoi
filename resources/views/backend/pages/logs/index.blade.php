@extends('backend.partials.main', ['title' => 'Log Sistem'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-file-document-outline"></i>
            </span>
            Log Sistem
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Log Sistem
                </li>
            </ul>
        </nav>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 stats-row-compact">
        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Total Entri</p>
                            <h3 class="stat-value">{{ number_format($stats['total']) }}</h3>
                        </div>
                        <i class="mdi mdi-format-list-bulleted stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Error / Critical</p>
                            <h3 class="stat-value">{{ number_format($stats['error']) }}</h3>
                        </div>
                        <i class="mdi mdi-alert-octagon stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Warning</p>
                            <h3 class="stat-value">{{ number_format($stats['warning']) }}</h3>
                        </div>
                        <i class="mdi mdi-alert stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card-compact bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="stat-label">Info / Debug</p>
                            <h3 class="stat-value">{{ number_format($stats['info'] + $stats['debug']) }}</h3>
                        </div>
                        <i class="mdi mdi-information stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-2">
                        <h4 class="card-title mb-0">Daftar Log</h4>

                        <div class="d-flex flex-wrap gap-2">
                            @if ($selectedFile)
                                <a href="{{ route('logs.download', ['file' => $selectedFile]) }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="mdi mdi-download"></i> Unduh
                                </a>

                                <form action="{{ route('logs.clear') }}" method="POST" class="d-inline"
                                    data-log-action="clear"
                                    data-log-message="Isi file &quot;{{ $selectedFile }}&quot; akan dikosongkan. Data log yang sudah ada tidak bisa dikembalikan.">
                                    @csrf
                                    <input type="hidden" name="file" value="{{ $selectedFile }}">
                                    <button type="submit" class="btn btn-outline-warning btn-sm">
                                        <i class="mdi mdi-broom"></i> Bersihkan
                                    </button>
                                </form>

                                <form action="{{ route('logs.destroy') }}" method="POST" class="d-inline"
                                    data-log-action="delete"
                                    data-log-message="File &quot;{{ $selectedFile }}&quot; akan dihapus permanen dari server.">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="file" value="{{ $selectedFile }}">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="mdi mdi-file-remove"></i> Hapus File
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('logs.prune-old') }}" method="POST" class="d-inline"
                                data-log-action="prune"
                                data-log-message="Semua file log yang lebih tua dari {{ $retentionDays }} hari akan dihapus permanen.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="mdi mdi-delete-sweep"></i> Hapus Log Lama (>{{ $retentionDays }}
                                    hari)
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($files->isEmpty())
                        <div class="text-center py-5">
                            <i class="mdi mdi-file-document-outline mdi-48px text-muted"></i>
                            <h5 class="text-muted mt-2">Belum ada file log</h5>
                            <p class="text-muted">File log akan muncul di sini setelah aplikasi mencatat aktivitas.
                            </p>
                        </div>
                    @else
                        <form method="GET" action="{{ route('logs.index') }}" class="row g-3 align-items-end mb-4">
                            <div class="col-lg-3 col-md-6">
                                <label for="logFile" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-calendar me-1"></i>File Log
                                </label>
                                <select name="file" id="logFile" class="form-select" onchange="this.form.submit()">
                                    @foreach ($files as $file)
                                        <option value="{{ $file['filename'] }}"
                                            {{ $selectedFile === $file['filename'] ? 'selected' : '' }}>
                                            {{ $file['label'] }} ({{ $file['size'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <label for="logLevel" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-filter-variant me-1"></i>Level
                                </label>
                                <select name="level" id="logLevel" class="form-select" onchange="this.form.submit()">
                                    <option value="">Semua Level</option>
                                    @foreach ($levels as $lvl)
                                        <option value="{{ $lvl }}" {{ $level === $lvl ? 'selected' : '' }}>
                                            {{ ucfirst($lvl) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-8">
                                <label for="logSearch" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-magnify me-1"></i>Cari Pesan
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="logSearch" name="search"
                                        placeholder="Ketik kata kunci..." value="{{ $search }}">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="mdi mdi-magnify"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-2 col-md-4">
                                <label for="logPerPage" class="form-label fw-semibold mb-1">
                                    <i class="mdi mdi-table-row me-1"></i>Per Halaman
                                </label>
                                <select name="per_page" id="logPerPage" class="form-select"
                                    onchange="this.form.submit()">
                                    @foreach ([10, 25, 50, 100, 200] as $pp)
                                        <option value="{{ $pp }}"
                                            {{ (int) request('per_page', 25) === $pp ? 'selected' : '' }}>
                                            {{ $pp }} baris</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-1 col-md-4">
                                <a href="{{ route('logs.index', ['file' => $selectedFile]) }}"
                                    class="btn btn-outline-secondary w-100"
                                    title="Reset filter">
                                    <i class="mdi mdi-refresh"></i>
                                </a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped log-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th style="width: 90px;">Waktu</th>
                                        <th style="width: 110px;">Level</th>
                                        <th>Pesan</th>
                                        <th style="width: 90px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($logs as $index => $entry)
                                        @php
                                            $levelBadge = match ($entry['level']) {
                                                'emergency', 'alert', 'critical' => 'bg-dark',
                                                'error' => 'bg-danger',
                                                'warning' => 'bg-warning text-dark',
                                                'notice' => 'bg-info',
                                                'info' => 'bg-primary',
                                                default => 'bg-secondary',
                                            };
                                            $rowId = 'log-row-' . ($logs->firstItem() + $index);
                                        @endphp
                                        <tr>
                                            <td>{{ $logs->firstItem() + $index }}</td>
                                            <td>
                                                <span class="d-block text-nowrap">{{ $entry['date'] }}</span>
                                                <small class="text-muted">{{ $entry['time'] }}</small>
                                            </td>
                                            <td>
                                                <span class="badge {{ $levelBadge }} text-uppercase">{{ $entry['level'] }}</span>
                                                @if ($entry['channel'])
                                                    <br><small class="text-muted">{{ $entry['channel'] }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="log-message">{{ Str::limit($entry['message'], 220) }}
                                                </div>
                                            </td>
                                            <td>
                                                @if ($entry['context'])
                                                    <button type="button" class="btn btn-sm btn-outline-secondary log-toggle"
                                                        data-target="{{ $rowId }}">
                                                        <i class="mdi mdi-eye-outline"></i> Detail
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if ($entry['context'])
                                            <tr id="{{ $rowId }}" class="log-detail-row d-none">
                                                <td colspan="5">
                                                    <pre class="log-context">{{ $entry['context'] }}</pre>
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="mdi mdi-file-search-outline mdi-48px text-muted"></i>
                                                <h5 class="text-muted mt-2">Tidak ada log yang cocok</h5>
                                                <p class="text-muted">Coba ubah kata kunci pencarian atau filter
                                                    level.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($logs->hasPages())
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                                <small class="text-muted">
                                    Menampilkan {{ $logs->firstItem() }}-{{ $logs->lastItem() }} dari
                                    {{ $logs->total() }} entri
                                </small>
                                {{ $logs->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* ===========================================
                                                                                                                                                                               STATISTICS CARDS (COMPACT)
                                                                                                                                                                            =========================================== */
        .stats-row-compact {
            margin-bottom: 1rem;
        }

        .stat-card-compact {
            border: none;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }

        .stat-card-compact .card-body {
            padding: 0.85rem 1rem;
        }

        .stat-card-compact .stat-label {
            margin: 0 0 2px;
            font-size: 0.75rem;
            font-weight: 500;
            opacity: 0.9;
            white-space: nowrap;
        }

        .stat-card-compact .stat-value {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .stat-card-compact .stat-icon {
            font-size: 1.6rem;
            opacity: 0.55;
            flex-shrink: 0;
            margin-left: 0.5rem;
        }

        @media (max-width: 576px) {
            .stat-card-compact .stat-value {
                font-size: 1.25rem;
            }

            .stat-card-compact .stat-icon {
                font-size: 1.3rem;
            }
        }

        .log-table td {
            vertical-align: top;
        }

        .log-message {
            font-family: 'Courier New', monospace;
            font-size: 0.82rem;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .log-detail-row td {
            background-color: #f8f9fa;
        }

        .log-context {
            max-height: 320px;
            margin: 0;
            padding: 12px;
            overflow: auto;
            background-color: #212529;
            color: #e9ecef;
            border-radius: 6px;
            font-size: 0.78rem;
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Expand/collapse the raw context of a log entry
            document.querySelectorAll('.log-toggle').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const target = document.getElementById(this.dataset.target);
                    if (!target) return;
                    target.classList.toggle('d-none');
                    this.innerHTML = target.classList.contains('d-none') ?
                        '<i class="mdi mdi-eye-outline"></i> Detail' :
                        '<i class="mdi mdi-eye-off-outline"></i> Tutup';
                });
            });

            // Confirm before clearing/deleting a log file
            const messages = {
                clear: 'Bersihkan Log?',
                delete: 'Hapus File Log?',
                prune: 'Hapus Log Lama?',
            };

            document.querySelectorAll('form[data-log-action]').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const action = form.dataset.logAction;

                    Swal.fire({
                        title: messages[action] || 'Konfirmasi',
                        text: form.dataset.logMessage || '',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, lanjutkan',
                        cancelButtonText: 'Batal',
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
