@extends('backend.partials.main', ['title' => 'Buat Peta'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-layers-plus"></i>
            </span>
            Buat Peta Baru
        </h3>
    </div>

    <div class="row">
        <div class="col-lg-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('maps.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Judul Peta</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                            @error('title') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">OPD Pemilik</label>
                                <select name="owner_opd_id" class="form-select">
                                    <option value="">Tidak ada</option>
                                    @foreach ($opdOptions as $opd)
                                        <option value="{{ $opd->id }}" @selected(old('owner_opd_id') == $opd->id)>{{ $opd->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Visibilitas</label>
                                <select name="visibility" class="form-select" required>
                                    <option value="private" @selected(old('visibility', 'private') === 'private')>Privat</option>
                                    <option value="public" @selected(old('visibility') === 'public')>Publik</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gradient-primary">Buat & Lanjut Susun Layer</button>
                        <a href="{{ route('maps.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
