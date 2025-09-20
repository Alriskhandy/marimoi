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
            DB::beginTransaction();

            // Create download record
            $download = PublicationDownload::create([
                'publication_id' => $publication->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'organization' => $request->organization,
                'position' => $request->position,
                'purpose' => $request->purpose,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'downloaded_at' => now(),
            ]);

            // Create survey record
            Survey::create([
                'publication_id' => $publication->id,
                'publication_download_id' => $download->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'organization' => $request->organization,
                'position' => $request->position,
                'survey_type' => 'download',
                'rating' => $request->rating,
                'feedback' => $request->feedback,
                'suggestions' => $request->suggestions,
                'additional_data' => $request->additional_data,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Increment download count
            $publication->incrementDownloadCount();

            DB::commit();

            // Return file download
            if (!Storage::disk('public')->exists($publication->file_path)) {
                abort(404, 'File not found');
            }

            return Storage::disk('public')->download(
                $publication->file_path, 
                $publication->file_name
            );

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->withErrors(['error' => 'Terjadi kesalahan saat memproses download. Silakan coba lagi.']);
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

        $callback = function() use ($downloads) {
            $file = fopen('php://output', 'w');
            
            // Header CSV
            fputcsv($file, [
                'ID', 'Publikasi', 'Nama', 'Email', 'Telepon', 'Organisasi', 
                'Posisi', 'Tujuan', 'Rating', 'IP Address', 'Tanggal Download'
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