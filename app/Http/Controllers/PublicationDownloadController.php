<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadSurveyRequest;
use App\Models\Publication;
use App\Models\PublicationDownload;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicationDownloadController extends Controller
{

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


    /**
     * Display a listing of all publication downloads
     */
    public function index(Request $request)
    {
        $query = PublicationDownload::with('publication')
            ->orderBy('downloaded_at', 'desc');

        // Filter by publication
        if ($request->filled('publication_id')) {
            $query->where('publication_id', $request->publication_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('downloaded_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('downloaded_at', '<=', $request->end_date);
        }

        // Filter by organization
        if ($request->filled('organization')) {
            $query->where('organization', 'like', '%' . $request->organization . '%');
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('organization', 'like', '%' . $search . '%')
                    ->orWhere('position', 'like', '%' . $search . '%');
            });
        }

        $downloads = $query->paginate(15)->withQueryString();
        $publications = Publication::orderBy('title')->get();

        return view('backend.pages.publikasi.user_download', compact('downloads', 'publications'));
    }

    /**
     * Show downloads for specific publication
     */
    public function show(Publication $publication, Request $request)
    {
        $query = $publication->downloads()
            ->orderBy('downloaded_at', 'desc');

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('downloaded_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('downloaded_at', '<=', $request->end_date);
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('organization', 'like', '%' . $search . '%')
                    ->orWhere('position', 'like', '%' . $search . '%');
            });
        }

        $downloads = $query->paginate(15)->withQueryString();

        // Statistics
        $stats = [
            'total_downloads' => $publication->downloads()->count(),
            'unique_organizations' => $publication->downloads()->distinct('organization')->count('organization'),
            'downloads_this_month' => $publication->downloads()
                ->whereMonth('downloaded_at', Carbon::now()->month)
                ->whereYear('downloaded_at', Carbon::now()->year)
                ->count(),
            'downloads_today' => $publication->downloads()
                ->whereDate('downloaded_at', Carbon::today())
                ->count(),
        ];

        return view('backend.publications.downloads.show', compact('publication', 'downloads', 'stats'));
    }

    /**
     * Get download analytics data
     */
    public function analytics(Request $request)
    {
        $publicationId = $request->publication_id;
        $startDate = $request->start_date ?? Carbon::now()->subDays(30);
        $endDate = $request->end_date ?? Carbon::now();

        $query = PublicationDownload::query();

        if ($publicationId) {
            $query->where('publication_id', $publicationId);
        }

        $query->whereBetween('downloaded_at', [$startDate, $endDate]);

        // Downloads per day
        $downloadsPerDay = $query->clone()
            ->selectRaw('DATE(downloaded_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top organizations
        $topOrganizations = $query->clone()
            ->selectRaw('organization, COUNT(*) as count')
            ->whereNotNull('organization')
            ->where('organization', '!=', '')
            ->groupBy('organization')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Purpose distribution
        $purposeDistribution = $query->clone()
            ->selectRaw('purpose, COUNT(*) as count')
            ->whereNotNull('purpose')
            ->where('purpose', '!=', '')
            ->groupBy('purpose')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'downloads_per_day' => $downloadsPerDay,
            'top_organizations' => $topOrganizations,
            'purpose_distribution' => $purposeDistribution,
        ]);
    }

    /**
     * Export downloads data to CSV
     */
    public function export(Request $request)
    {
        $query = PublicationDownload::with('publication');

        // Apply filters
        if ($request->filled('publication_id')) {
            $query->where('publication_id', $request->publication_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('downloaded_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('downloaded_at', '<=', $request->end_date);
        }

        $downloads = $query->orderBy('downloaded_at', 'desc')->get();

        $filename = 'publication-downloads-' . Carbon::now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($downloads) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Publikasi',
                'Nama',
                'Email',
                'Telepon',
                'Organisasi',
                'Posisi',
                'Tujuan',
                'IP Address',
                'Tanggal Download',
            ]);

            foreach ($downloads as $download) {
                fputcsv($file, [
                    $download->id,
                    $download->publication->title,
                    $download->name,
                    $download->email,
                    $download->phone,
                    $download->organization,
                    $download->position,
                    $download->purpose,
                    $download->ip_address,
                    $download->downloaded_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete a download record
     */
    public function destroy(PublicationDownload $download)
    {
        try {
            $download->delete();

            return redirect()->back()->with('success', 'Data download berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data download');
        }
    }

    /**
     * Bulk delete download records
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'download_ids' => 'required|array',
            'download_ids.*' => 'exists:publication_downloads,id'
        ]);

        try {
            PublicationDownload::whereIn('id', $request->download_ids)->delete();

            return response()->json([
                'success' => true,
                'message' => count($request->download_ids) . ' data download berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data download'
            ], 500);
        }
    }
}
