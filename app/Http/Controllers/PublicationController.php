<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicationRequest;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicationController extends Controller
{
    /**
     * Display publications for frontend
     */
    public function index(Request $request)
    {
        $query = Publication::query();

        // Filter berdasarkan kategori
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Filter berdasarkan pencarian
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $publications = $query->orderBy('created_at', 'desc')->paginate(12);

        // Ambil kategori yang tersedia untuk filter
        $categories = Publication::whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('frontend.pages.publikasi', compact('publications', 'categories'));
    }

    /**
     * Show single publication for frontend
     */
    public function show(Publication $publication)
    {
        // Load related data
        $publication->load(['surveys' => function ($query) {
            $query->latest()->take(5);
        }]);

        return view('publications.show', compact('publication'));
    }

    /**
     * Display publications list for admin dashboard
     */
    public function adminIndex(Request $request)
    {
        $query = Publication::query();

        // Filter berdasarkan kategori
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Filter berdasarkan pencarian
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        $publications = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('backend.pages.publikasi.index', compact('publications'));
    }

    /**
     * Show the form for creating a new publication
     */
    public function create()
    {
        return view('backend.pages.publikasi.create');
    }

    /**
     * Store a newly created publication
     */
    public function store(PublicationRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Generate unique filename with clean name
            $originalName = $file->getClientOriginalName();
            $cleanName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . $cleanName . '.' . $extension;

            // Store file in dokumen_files folder
            $path = $file->storeAs('dokumen_files', $filename, 'public');

            $data['file_name'] = $originalName;
            $data['file_path'] = $path;
            $data['file_type'] = $extension;
            $data['file_size'] = $file->getSize();
        }

        Publication::create($data);

        return redirect()->route('publications.index')
            ->with('success', 'Publikasi berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified publication
     */
    public function edit(Publication $publication)
    {
        return view('backend.pages.publikasi.edit', compact('publication'));
    }

    /**
     * Update the specified publication
     */
    public function update(PublicationRequest $request, Publication $publication)
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            // Delete old file
            if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
                Storage::disk('public')->delete($publication->file_path);
            }

            $file = $request->file('file');

            // Generate unique filename with clean name
            $originalName = $file->getClientOriginalName();
            $cleanName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . $cleanName . '.' . $extension;

            // Store file in dokumen_files folder
            $path = $file->storeAs('dokumen_files', $filename, 'public');

            $data['file_name'] = $originalName;
            $data['file_path'] = $path;
            $data['file_type'] = $extension;
            $data['file_size'] = $file->getSize();
        }

        $publication->update($data);

        return redirect()->route('publications.index')
            ->with('success', 'Publikasi berhasil diupdate.');
    }

    /**
     * Remove the specified publication
     */
    public function destroy(Publication $publication)
    {
        // Delete file if exists
        if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
            Storage::disk('public')->delete($publication->file_path);
        }

        $publication->delete();

        return redirect()->route('publications.index')
            ->with('success', 'Publikasi berhasil dihapus.');
    }

    /**
     * Preview file in browser
     */
    public function preview(Publication $publication)
    {
        if (!Storage::disk('public')->exists($publication->file_path)) {
            abort(404, 'File tidak ditemukan');
        }

        $fullPath = Storage::disk('public')->path($publication->file_path);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $publication->file_name . '"'
        ]);
    }

    /**
     * Download publication file
     */
    public function download(Publication $publication)
    {
        if (!Storage::disk('public')->exists($publication->file_path)) {
            abort(404, 'File tidak ditemukan');
        }

        // Increment download count
        $publication->increment('download_count');

        return Storage::disk('public')->download($publication->file_path, $publication->file_name);
    }

    /**
     * Bulk delete publications
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'publication_ids' => 'required|array',
            'publication_ids.*' => 'exists:publications,id'
        ]);

        $publications = Publication::whereIn('id', $request->publication_ids)->get();

        foreach ($publications as $publication) {
            // Delete file if exists
            if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
                Storage::disk('public')->delete($publication->file_path);
            }

            $publication->delete();
        }

        return redirect()->back()
            ->with('success', count($request->publication_ids) . ' publikasi berhasil dihapus.');
    }

    /**
     * Get publication statistics for dashboard
     */
    public function getStats()
    {
        return [
            'total' => Publication::count(),
            'total_downloads' => Publication::sum('download_count'),
            'recent' => Publication::latest()->take(5)->get(),
            'popular' => Publication::orderBy('download_count', 'desc')->take(5)->get(),
            'by_category' => Publication::selectRaw('category, COUNT(*) as count')
                ->whereNotNull('category')
                ->groupBy('category')
                ->get()
        ];
    }

    /**
     * Search publications for API/AJAX
     */
    public function search(Request $request)
    {
        $query = Publication::query();

        if ($request->has('q') && $request->q) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        $publications = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json($publications);
    }

    /**
     * Get publication by slug for SEO-friendly URLs
     */
    public function showBySlug($slug)
    {
        $publication = Publication::where('slug', $slug)
            ->firstOrFail();

        return view('publications.show', compact('publication'));
    }

    /**
     * Debug file information (development only)
     */
    public function debugFile($id)
    {
        if (!app()->environment('local')) {
            abort(404);
        }

        $publication = Publication::findOrFail($id);

        $debug = [
            'publication_info' => [
                'id' => $publication->id,
                'title' => $publication->title,
                'file_name' => $publication->file_name,
                'file_path' => $publication->file_path,
                'file_type' => $publication->file_type,
                'file_size' => $publication->file_size,
            ],
            'storage_info' => [
                'storage_disk_path' => Storage::disk('public')->path(''),
                'public_storage_path' => public_path('storage/'),
                'symlink_exists' => is_link(public_path('storage')),
                'symlink_target' => is_link(public_path('storage')) ? readlink(public_path('storage')) : 'Not a symlink'
            ],
            'file_checks' => [
                'exists_in_storage' => Storage::disk('public')->exists($publication->file_path),
                'full_path' => Storage::disk('public')->path($publication->file_path),
                'file_exists' => file_exists(Storage::disk('public')->path($publication->file_path)),
                'is_readable' => file_exists(Storage::disk('public')->path($publication->file_path)) ?
                    is_readable(Storage::disk('public')->path($publication->file_path)) : false,
                'file_size' => file_exists(Storage::disk('public')->path($publication->file_path)) ?
                    filesize(Storage::disk('public')->path($publication->file_path)) : 'N/A'
            ],
            'urls' => [
                'asset_url' => asset('storage/' . $publication->file_path),
                'preview_url' => route('publications.preview', $publication->id),
                'download_url' => route('publications.download', $publication->id)
            ],
            'model_methods' => [
                'file_exists' => $publication->fileExists(),
                'asset_url' => $publication->asset_url,
                'file_url' => $publication->file_url,
            ]
        ];

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
    }
}
