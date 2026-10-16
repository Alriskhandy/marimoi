@if (! $import)
    <div class="alert alert-warning">
        <i class="mdi mdi-alert-circle me-2"></i>
        Tidak ada data impor yang menunggu dipetakan. Silakan unggah file di tahap sebelumnya.
    </div>
    <a href="{{ route('spatial-layers.wizard', [$layer, 'step' => 2]) }}" class="btn btn-outline-secondary">
        <i class="mdi mdi-arrow-left"></i> Kembali ke Tahap Impor
    </a>
@else
    @php
        $sampleRow = $import->log['features'][0]['attributes'] ?? [];

        $typeOf = function ($value) {
            if ($value === null || $value === '') {
                return '—';
            }
            if (is_numeric($value)) {
                return str_contains((string) $value, '.') ? 'DOUBLE' : 'INTEGER';
            }

            return 'TEXT';
        };
    @endphp

    <h5 class="mb-1"><i class="mdi mdi-table-column me-2"></i>Pemetaan Atribut</h5>
    <p class="text-muted small mb-4">
        File <strong>{{ $import->original_filename }}</strong> &mdash;
        <strong>{{ $import->total_features }}</strong> fitur terdeteksi, mode impor
        <strong>{{ ucfirst($import->import_mode) }}</strong>. Tentukan kolom mana yang disimpan apa adanya
        dan mana yang diabaikan.
    </p>

    <form method="POST" action="{{ route('spatial-layers.wizard.mapping', $layer) }}">
        @csrf

        @forelse ($import->detected_fields as $field)
            @php $decision = old("mapping.$field", 'keep'); @endphp
            <div class="mapping-row" data-decision="{{ $decision }}">
                <div class="mapping-row-field">
                    <code>{{ $field }}</code>
                    <span class="mapping-type-badge">{{ $typeOf($sampleRow[$field] ?? null) }}</span>
                </div>
                <div class="mapping-row-sample">
                    <span class="text-muted small">Sampel:</span>
                    <span class="mapping-sample-value">{{ $sampleRow[$field] ?? '—' }}</span>
                </div>
                <div class="mapping-row-target">
                    <select name="mapping[{{ $field }}]" class="form-select mapping-decision-select">
                        <option value="keep" @selected($decision === 'keep')>Simpan apa adanya</option>
                        <option value="ignore" @selected($decision === 'ignore')>Abaikan (jangan simpan)</option>
                    </select>
                </div>
                <div class="mapping-row-action">
                    <i class="mdi mapping-action-icon"></i>
                </div>
            </div>
        @empty
            <p class="text-muted">Tidak ada kolom terdeteksi dari file ini.</p>
        @endforelse

        <div class="mt-4 d-flex justify-content-between">
            <a href="{{ route('spatial-layers.wizard', [$layer, 'step' => 2]) }}" class="btn btn-outline-secondary">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="btn btn-gradient-success">
                <i class="mdi mdi-check me-1"></i> Proses & Lanjut
            </button>
        </div>
    </form>
@endif

@push('styles')
    <style>
        .mapping-row {
            display: grid;
            grid-template-columns: 1.4fr 1.6fr 1.6fr 48px;
            gap: 1rem;
            align-items: center;
            padding: 0.85rem 1rem;
            border: 1px solid #eef2f7;
            border-radius: 10px;
            background: #fff;
            margin-bottom: 0.6rem;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .mapping-row:hover {
            border-color: #cfe2ff;
        }

        .mapping-row[data-decision="ignore"] {
            background: #f8f9fa;
            opacity: 0.75;
        }

        .mapping-row-field code {
            font-size: 0.85rem;
            font-weight: 700;
            color: #0d6efd;
            background: #eef5ff;
            padding: 2px 8px;
            border-radius: 5px;
        }

        .mapping-type-badge {
            display: inline-block;
            margin-left: 6px;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.3px;
            color: #6c757d;
            background: #f1f3f5;
            border-radius: 4px;
            padding: 1px 6px;
            vertical-align: middle;
        }

        .mapping-row-sample {
            overflow: hidden;
        }

        .mapping-sample-value {
            display: block;
            font-family: 'Courier New', monospace;
            font-size: 0.82rem;
            color: #495057;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .mapping-row-action {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin: 0 auto;
        }

        .mapping-row[data-decision="keep"] .mapping-row-action {
            background: #d1e7dd;
        }

        .mapping-row[data-decision="keep"] .mapping-action-icon::before {
            content: "\F012C";
            color: #198754;
        }

        .mapping-row[data-decision="ignore"] .mapping-row-action {
            background: #f8d7da;
        }

        .mapping-row[data-decision="ignore"] .mapping-action-icon::before {
            content: "\F0156";
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .mapping-row {
                grid-template-columns: 1fr;
                row-gap: 0.5rem;
            }

            .mapping-row-action {
                justify-self: start;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
            $(document).on('change', '.mapping-decision-select', function() {
                $(this).closest('.mapping-row').attr('data-decision', $(this).val());
            });
        });
    </script>
@endpush
