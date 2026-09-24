@extends('backend.partials.main')

@section('main')
<div class="page-header">
    <h3 class="page-title">Perbarui Laporan Progres — {{ $proyek->deskripsi }}</h3>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted">
            Periode: <strong>{{ $laporan->periode_laporan }} {{ $laporan->tahun_anggaran }}</strong>
            (tidak bisa diubah — perbarui hanya nilai finansial/progres di bawah ini).
            Nilai sebelum pembaruan ini akan tersimpan otomatis di riwayat perubahan.
        </p>

        <form method="POST" action="{{ route('project-progress.laporan.update', [$proyek->uuid, $laporan->id]) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Pagu (Rp)</label>
                <input type="number" step="0.01" name="pagu" class="form-control" value="{{ old('pagu', $laporan->pagu) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Realisasi Anggaran (Rp)</label>
                <input type="number" step="0.01" name="realisasi_anggaran" class="form-control" value="{{ old('realisasi_anggaran', $laporan->realisasi_anggaran) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Progres Fisik (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="progres_fisik_persen"
                    class="form-control @error('progres_fisik_persen') is-invalid @enderror"
                    value="{{ old('progres_fisik_persen', $laporan->progres_fisik_persen) }}" required>
                @error('progres_fisik_persen') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(old('status', $laporan->status) === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $laporan->catatan) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Pembaruan</button>
            <a href="{{ route('project-progress.show', $proyek->uuid) }}" class="btn btn-outline-secondary">Batal</a>
        </form>
    </div>
</div>
@endsection
