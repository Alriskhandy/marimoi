@extends('frontend.layouts.spatial', ['title' => 'Lacak Aspirasi', 'heroTitle' => 'Lacak Status Aspirasi'])

@section('main')
    <section class="min-h-screen mt-0 pt-8 pb-8 bg-slate-50">
        <div class="container mx-auto px-4 max-w-xl">
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-6">
                <h3 class="text-lg text-center font-semibold mb-2 text-slate-800">Lacak Status Aspirasi</h3>
                <p class="text-center text-slate-600 mb-6 text-sm">
                    Masukkan nomor tiket yang Anda terima saat mengirim aspirasi, beserta email atau nomor WhatsApp yang Anda daftarkan.
                </p>

                <form action="{{ route('aspirasi-masyarakat.lacak.cari') }}" method="post" class="space-y-4">
                    @csrf
                    <div>
                        <label for="nomor_tiket" class="block text-sm font-medium text-slate-700 mb-1">Nomor Tiket</label>
                        <input type="text" id="nomor_tiket" name="nomor_tiket"
                            value="{{ old('nomor_tiket') }}" placeholder="MARIMOI-ASP-20260101-0001"
                            class="w-full rounded-lg border-slate-300 @error('nomor_tiket') border-red-500 @enderror" required>
                        @error('nomor_tiket')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="kontak" class="block text-sm font-medium text-slate-700 mb-1">Email atau Nomor WhatsApp</label>
                        <input type="text" id="kontak" name="kontak" placeholder="nama@email.com atau 0812xxxxxxx"
                            class="w-full rounded-lg border-slate-300 @error('kontak') border-red-500 @enderror" required>
                        @error('kontak')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 text-white py-2.5 font-medium hover:bg-blue-700">
                        Cek Status
                    </button>
                </form>

                @if (! empty($notFound))
                    <div class="mt-6 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-4">
                        Nomor tiket tidak ditemukan, atau email/nomor WhatsApp tidak cocok dengan data pengajuan.
                        Periksa kembali nomor tiket pada email konfirmasi Anda.
                    </div>
                @endif

                @isset($aspirasi)
                    @php
                        $steps = ['pending' => 0, 'diproses' => 1, 'selesai' => 2];
                        $currentStep = $steps[$aspirasi->status] ?? 0;
                        $ditolak = $aspirasi->status === 'ditolak';
                    @endphp
                    <div class="mt-6 border-t border-slate-200 pt-6">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-slate-800">{{ $aspirasi->judul_aspirasi }}</h4>
                            <span class="text-xs px-3 py-1 rounded-full font-medium
                                @class([
                                    'bg-yellow-100 text-yellow-700' => $aspirasi->status === 'pending',
                                    'bg-blue-100 text-blue-700' => $aspirasi->status === 'diproses',
                                    'bg-green-100 text-green-700' => $aspirasi->status === 'selesai',
                                    'bg-red-100 text-red-700' => $ditolak,
                                ])">
                                {{ ucfirst($aspirasi->status) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mb-4">Nomor tiket: {{ $aspirasi->nomor_tiket }} &middot; Diajukan {{ $aspirasi->created_at->format('d M Y') }}</p>

                        @if ($ditolak)
                            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-4 mb-4">
                                Aspirasi ini tidak dapat kami tindak lanjuti.
                            </div>
                        @else
                            <ol class="flex items-center w-full mb-4 text-xs">
                                @foreach (['Diterima', 'Diproses', 'Selesai'] as $i => $label)
                                    <li class="flex-1 flex flex-col items-center {{ !$loop->last ? 'relative' : '' }}">
                                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px]
                                            {{ $i <= $currentStep ? 'bg-blue-600' : 'bg-slate-300' }}">{{ $i + 1 }}</div>
                                        <span class="mt-1 text-slate-600">{{ $label }}</span>
                                    </li>
                                @endforeach
                            </ol>
                            <p class="text-xs text-slate-400 mb-4">Menampilkan status terkini, bukan riwayat lengkap perubahan.</p>
                        @endif

                        <div class="text-sm">
                            <span class="text-slate-500">Tanggapan{{ $aspirasi->tanggal_respon ? ' ('.$aspirasi->tanggal_respon->format('d M Y').')' : '' }}:</span>
                            <p class="mt-1 whitespace-pre-line text-slate-700">
                                {{ $aspirasi->tanggapan_admin ?: 'Belum ada tanggapan. Mohon tunggu, tim kami akan segera memproses.' }}
                            </p>
                        </div>
                    </div>
                @endisset
            </div>
        </div>
    </section>
@endsection
