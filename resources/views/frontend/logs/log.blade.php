@extends('frontend.layouts.dark')

@push('styles')
    @vite(['resources/css/app.css'])
@endpush

@section('main')
    <section class="min-h-screen mt-[76px] pt-8 pb-8 bg-slate-50">
        <div class="container mx-auto px-4">
            <div class="bg-white rounded-2xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Monitoring Log Laravel</h2>
                    <div class="flex items-center gap-2">
                        <form id="searchForm" method="GET" class="flex items-center gap-2">
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari teks log"
                                class="px-3 py-2 border rounded-md text-sm" />
                            <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded-md text-sm">Cari</button>
                        </form>
                    </div>
                </div>

                <div class="mb-3 text-sm text-gray-600">Menampilkan <strong>{{ $linesShown }}</strong> baris dari file:
                    <code>{{ $logFile ?? 'storage/logs/laravel.log' }}</code>
                </div>

                <div class="overflow-auto border rounded-md bg-white text-gray-800" style="max-height:75vh;">
                    @if (empty($parsedLogs) || count($parsedLogs) === 0)
                        <div class="text-center text-gray-500 p-8">Tidak ada log yang cocok.</div>
                    @else
                        <table class="w-full table-fixed text-sm">
                            <thead class="sticky top-0 bg-gray-50 border-b">
                                <tr class="text-left">
                                    <th class="px-4 py-3 font-semibold text-gray-700 w-32">Tanggal</th>
                                    <th class="px-4 py-3 font-semibold text-gray-700 w-24">Waktu</th>
                                    <th class="px-4 py-3 font-semibold text-gray-700">Isi Log</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($parsedLogs as $row)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-4 py-3 align-top text-gray-600 font-mono text-xs">
                                            {{ $row['date'] ?? '-' }}</td>
                                        <td class="px-4 py-3 align-top text-gray-600 font-mono text-xs">
                                            {{ $row['time'] ?? '-' }}</td>
                                        <td class="px-4 py-3 align-top">
                                            <div
                                                class="font-mono text-xs leading-relaxed word-break break-words overflow-hidden">
                                                {{ $row['content'] }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-600">Ukuran file: {{ $sizeHuman }}</div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush
