<?php
// app/Http/Controllers/PublicationDownloadController.php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadSurveyRequest;
use App\Models\Publication;
use App\Models\PublicationDownload;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublicationDownloadController extends Controller
{
    public function showSurveyForm(Publication $publication)
    {
        if (!$publication->is_active) {
            abort(404);
        }

        return view('publications.download-survey', compact('publication'));
    }

    public function processSurveyAndDownload(DownloadSurveyRequest $request, Publication $publication)
    {
        if (!$publication->is_active) {
            abort(404);
        }

        try {
            // Pastikan file ada sebelum lanjut
            if (!Storage::disk('public')->exists($publication->file_path)) {
                Log::error('File not found for publication', [
                    'publication_id' => $publication->id,
                    'file_path' => $publication->file_path
                ]);
                abort(404, 'File not found');
            }

            DB::beginTransaction();

            // Simpan data survey download
            PublicationDownload::create([
                'publication_id' => $publication->id,
                'name'           => $request->name,
                'email'          => $request->email,
                'phone'          => $request->phone,
                'organization'   => $request->organization,
                'position'       => $request->position,
                'purpose'        => $request->purpose,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'downloaded_at'  => now(),
            ]);

            // Increment download count
            $publication->incrementDownloadCount();

            DB::commit();

            Log::info('Download recorded successfully', [
                'publication_id' => $publication->id,
                'user_email'     => $request->email,
            ]);

            // Get file info
            $filePath = Storage::disk('public')->path($publication->file_path);
            $fileName = $publication->file_name ?? basename($publication->file_path);

            Log::info('Processing file download', [
                'file_path' => $filePath,
                'file_name' => $fileName,
                'exists' => file_exists($filePath)
            ]);

            // Pastikan file benar-benar ada di filesystem
            if (!file_exists($filePath)) {
                Log::error('Physical file not found', ['file_path' => $filePath]);
                throw new \Exception('File tidak ditemukan di server');
            }

            // Return download response dengan header yang tepat
            return Storage::disk('public')->download(
                $publication->file_path,
                $fileName,
                [
                    'Content-Type' => Storage::disk('public')->mimeType($publication->file_path)
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error processing survey & download', [
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
                'publication_id' => $publication->id ?? null,
                'file_path'      => $publication->file_path ?? null,
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Terjadi kesalahan saat memproses download: ' . $e->getMessage()]);
        }
    }


    // Admin Methods
    public function index(Request $request)
    {
        $query = PublicationDownload::with(['publication', 'survey']);

        // Filter berdasarkan publikasi
        if ($request->has('publication_id') && $request->publication_id) {
            $query->where('publication_id', $request->publication_id);
        }

        // Filter berdasarkan tanggal
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('downloaded_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('downloaded_at', '<=', $request->date_to);
        }

        // Filter berdasarkan pencarian email/nama
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('organization', 'like', "%{$search}%");
            });
        }

        $downloads = $query->orderBy('downloaded_at', 'desc')->paginate(20);

        // Ambil daftar publikasi untuk filter
        $publications = Publication::select('id', 'title')->orderBy('title')->get();

        return view('admin.downloads.index', compact('downloads', 'publications'));
    }

    public function show(PublicationDownload $download)
    {
        $download->load(['publication', 'survey']);
        return view('admin.downloads.show', compact('download'));
    }

    public function exportDownloads(Request $request)
    {
        $query = PublicationDownload::with(['publication', 'survey']);

        // Filter berdasarkan publikasi
        if ($request->has('publication_id') && $request->publication_id) {
            $query->where('publication_id', $request->publication_id);
        }

        // Filter berdasarkan tanggal
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('downloaded_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('downloaded_at', '<=', $request->date_to);
        }

        $downloads = $query->orderBy('downloaded_at', 'desc')->get();

        // Return sebagai CSV
        $filename = 'downloads_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($downloads) {
            $file = fopen('php://output', 'w');

            // Header CSV
            fputcsv($file, [
                'ID',
                'Publikasi',
                'Nama',
                'Email',
                'Telepon',
                'Organisasi',
                'Posisi',
                'Tujuan',
                'Rating',
                'IP Address',
                'Tanggal Download'
            ]);

            // Data
            foreach ($downloads as $download) {
                fputcsv($file, [
                    $download->id,
                    $download->publication ? $download->publication->title : '-',
                    $download->name,
                    $download->email,
                    $download->phone,
                    $download->organization,
                    $download->position,
                    $download->purpose,
                    $download->survey ? $download->survey->rating : '-',
                    $download->ip_address,
                    $download->downloaded_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
