@extends('backend.partials.main', ['title' => 'Feedback Pemetaan'])

@section('main')
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2"><i class="mdi mdi-message-reply-text"></i></span>
            Feedback Pemetaan
        </h3>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="baru" @selected(request('status') === 'baru')>Baru</option>
                <option value="ditinjau" @selected(request('status') === 'ditinjau')>Ditinjau</option>
                <option value="ditanggapi" @selected(request('status') === 'ditanggapi')>Ditanggapi</option>
            </select>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Target</th>
                            <th>Pemberi</th>
                            <th>Pesan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($feedbacks as $feedback)
                            <tr>
                                <td>
                                    @if ($feedback->layer)
                                        Layer: {{ $feedback->layer->name }}
                                    @elseif ($feedback->feature)
                                        Data Spasial #{{ $feedback->feature->id }} ({{ $feedback->feature->layer?->name }})
                                    @endif
                                </td>
                                <td>{{ $feedback->nama_pemberi }}<br><small class="text-muted">{{ $feedback->email }}</small></td>
                                <td>{{ \Illuminate\Support\Str::limit($feedback->pesan, 80) }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($feedback->status) }}</span></td>
                                <td>
                                    <form method="POST" action="{{ route('spatial-feedbacks.respond', $feedback) }}" class="d-flex flex-column gap-1">
                                        @csrf
                                        @method('PUT')
                                        <textarea name="response_admin" class="form-control form-control-sm" rows="2" placeholder="Tanggapan...">{{ $feedback->response_admin }}</textarea>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="baru" @selected($feedback->status === 'baru')>Baru</option>
                                            <option value="ditinjau" @selected($feedback->status === 'ditinjau')>Ditinjau</option>
                                            <option value="ditanggapi" @selected($feedback->status === 'ditanggapi')>Ditanggapi</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">Belum ada feedback.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $feedbacks->links() }}
        </div>
    </div>
@endsection
