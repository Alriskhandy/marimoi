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
        // if (!$publication->is_active) {
        //     if ($request->ajax()) {
        //         return response()->json(['success' => false, 'message' => 'Publikasi tidak aktif'], 404);
        //     }
        //     abort(404);
        // }

        try {
            // Validasi file dengan lebih detail
            $fileValidation = $this->validateFileForDownload($publication);

            if (!$fileValidation['valid']) {
                Log::error('File validation failed', [
                    'publication_id' => $publication->id,
                    'file_path' => $publication->file_path,
                    'error' => $fileValidation['message']
                ]);

                return $this->handleError($request, $fileValidation['message']);
            }

            DB::beginTransaction();

            try {
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
                $publication->increment('download_count');

                DB::commit();

                Log::info('Download recorded successfully', [
                    'publication_id' => $publication->id,
                    'user_email'     => $request->email,
                    'file_size'      => $fileValidation['size'] ?? 'unknown'
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            // Siapkan file untuk download
            $downloadFileName = $this->prepareDownloadFilename($publication);

            Log::info('Starting file download', [
                'publication_id' => $publication->id,
                'file_path' => $fileValidation['path'],
                'download_name' => $downloadFileName,
                'file_size' => $fileValidation['size']
            ]);

            // Return download response
            return response()->download(
                $fileValidation['path'],
                $downloadFileName,
                [
                    'Content-Type' => $this->getMimeType($publication->file_path),
                    'Content-Length' => $fileValidation['size'],
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'Content-Disposition' => 'attachment; filename="' . $downloadFileName . '"'
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error processing survey & download', [
                'error'          => $e->getMessage(),
                'trace'          => $e->getTraceAsString(),
                'publication_id' => $publication->id ?? null,
                'file_path'      => $publication->file_path ?? null,
                'line'           => $e->getLine(),
                'file'           => $e->getFile()
            ]);

            return $this->handleError($request, 'Terjadi kesalahan saat memproses download. Silakan coba lagi.');
        }
    }

    /**
     * Validasi file sebelum download
     */
    private function validateFileForDownload(Publication $publication): array
    {
        $result = ['valid' => false, 'message' => '', 'path' => '', 'size' => 0];

        // Cek apakah file_path ada
        if (empty($publication->file_path)) {
            $result['message'] = 'Path file tidak ditemukan di database';
            return $result;
        }

        // Cek apakah file ada di storage
        if (!Storage::disk('public')->exists($publication->file_path)) {
            $result['message'] = 'File tidak ditemukan dalam storage';
            Log::error('File not found in storage', [
                'file_path' => $publication->file_path,
                'storage_path' => Storage::disk('public')->path($publication->file_path)
            ]);
            return $result;
        }

        // Get full filesystem path
        $fullPath = Storage::disk('public')->path($publication->file_path);

        // Cek apakah file fisik ada
        if (!file_exists($fullPath)) {
            $result['message'] = 'File tidak ditemukan pada filesystem';
            Log::error('Physical file not found', [
                'full_path' => $fullPath,
                'storage_exists' => Storage::disk('public')->exists($publication->file_path)
            ]);
            return $result;
        }

        // Cek apakah file bisa dibaca
        if (!is_readable($fullPath)) {
            $result['message'] = 'File tidak dapat dibaca';
            Log::error('File not readable', [
                'full_path' => $fullPath,
                'permissions' => fileperms($fullPath)
            ]);
            return $result;
        }

        // Cek ukuran file
        $fileSize = filesize($fullPath);
        if ($fileSize === false || $fileSize == 0) {
            $result['message'] = 'File kosong atau corrupt';
            Log::error('File empty or corrupt', [
                'full_path' => $fullPath,
                'file_size' => $fileSize
            ]);
            return $result;
        }

        $result['valid'] = true;
        $result['path'] = $fullPath;
        $result['size'] = $fileSize;

        return $result;
    }

    /**
     * Siapkan nama file untuk download
     */
    private function prepareDownloadFilename(Publication $publication): string
    {
        // Gunakan file_name dari database atau fallback ke title
        $downloadFileName = $publication->file_name ?? $publication->title ?? basename($publication->file_path);

        // Bersihkan nama file dari karakter yang tidak diinginkan
        $downloadFileName = preg_replace('/[^a-zA-Z0-9\-_\.\s]/', '', $downloadFileName);
        $downloadFileName = trim($downloadFileName);

        // Pastikan nama file memiliki ekstensi
        $hasExtension = pathinfo($downloadFileName, PATHINFO_EXTENSION);
        if (!$hasExtension) {
            $extension = pathinfo($publication->file_path, PATHINFO_EXTENSION);
            if ($extension) {
                $downloadFileName .= '.' . $extension;
            } else {
                $downloadFileName .= '.pdf'; // default extension
            }
        }

        return $downloadFileName;
    }

    /**
     * Get MIME type for file
     */
    private function getMimeType(string $filePath): string
    {
        try {
            $mimeType = Storage::disk('public')->mimeType($filePath);
            if ($mimeType) {
                return $mimeType;
            }
        } catch (\Exception $e) {
            Log::warning('Could not determine MIME type', ['file_path' => $filePath, 'error' => $e->getMessage()]);
        }

        // Fallback berdasarkan ekstensi
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Handle error response berdasarkan tipe request
     */
    private function handleError(Request $request, string $message)
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }

        return back()
            ->withInput()
            ->withErrors(['error' => $message]);
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
