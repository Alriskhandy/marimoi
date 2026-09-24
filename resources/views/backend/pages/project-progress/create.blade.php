@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">Tambah Laporan Progres — {{ $proyek->deskripsi }}</h3>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('project-progress.store', $proyek->uuid) }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Tahun Anggaran</label>
                <input type="number" name="tahun_anggaran" class="form-control @error('tahun_anggaran') is-invalid @enderror"
                    value="{{ old('tahun_anggaran', now()->year) }}" required>
                @error('tahun_anggaran') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Periode Laporan</label>
                <select name="periode_laporan" class="form-select @error('periode_laporan') is-invalid @enderror" required>
                    <option value="">Pilih periode</option>
                    @foreach ($periodeOptions as $periode)
                        <option value="{{ $periode }}" @selected(old('periode_laporan') === $periode)>{{ $periode }}</option>
                    @endforeach
                </select>
                @error('periode_laporan') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Pagu (Rp)</label>
                <input type="number" step="0.01" name="pagu" class="form-control" value="{{ old('pagu') }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Realisasi Anggaran (Rp)</label>
                <input type="number" step="0.01" name="realisasi_anggaran" class="form-control" value="{{ old('realisasi_anggaran') }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Progres Fisik (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="progres_fisik_persen"
                    class="form-control @error('progres_fisik_persen') is-invalid @enderror" value="{{ old('progres_fisik_persen') }}" required>
                @error('progres_fisik_persen') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(old('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="catatan" class="form-control" rows="3">{{ old('catatan') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Laporan</button>
            <a href="{{ route('project-progress.show', $proyek->uuid) }}" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
@endsection
