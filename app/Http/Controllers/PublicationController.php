<?php
// app/Http/Controllers/PublicationController.php

namespace App\Http\Controllers;

use App\Http\Requests\PublicationRequest;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Publication::where('is_active', true);

        // Filter berdasarkan kategori
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Filter berdasarkan pencarian
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        $publications = $query->orderBy('created_at', 'desc')->paginate(12);

        // Ambil kategori yang tersedia untuk filter
        $categories = Publication::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('frontend.pages.publikasi', compact('publications', 'categories'));
    }

    public function show(Publication $publication)
    {
        if (!$publication->is_active) {
            abort(404);
        }

        // Load related data
        $publication->load(['surveys' => function ($query) {
            $query->latest()->take(5);
        }]);

        return view('publications.show', compact('publication'));
    }

    // Admin Methods
    public function create()
    {
        return view('admin.publications.create');
    }

    public function store(PublicationRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('publications', $filename, 'public');

            $data['file_name'] = $file->getClientOriginalName();
            $data['file_path'] = $path;
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

        Publication::create($data);

        return redirect()->route('admin.publications.index')
            ->with('success', 'Publikasi berhasil dibuat.');
    }

    public function edit(Publication $publication)
    {
        return view('admin.publications.edit', compact('publication'));
    }

    public function update(PublicationRequest $request, Publication $publication)
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            // Delete old file
            if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
                Storage::disk('public')->delete($publication->file_path);
            }

            $file = $request->file('file');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('publications', $filename, 'public');

            $data['file_name'] = $file->getClientOriginalName();
            $data['file_path'] = $path;
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
        }

        $publication->update($data);

        return redirect()->route('admin.publications.index')
            ->with('success', 'Publikasi berhasil diupdate.');
    }

    public function destroy(Publication $publication)
    {
        if ($publication->file_path && Storage::disk('public')->exists($publication->file_path)) {
            Storage::disk('public')->delete($publication->file_path);
        }

        $publication->delete();

        return redirect()->route('admin.publications.index')
            ->with('success', 'Publikasi berhasil dihapus.');
    }

    // Admin list publications
    public function adminIndex(Request $request)
    {
        $query = Publication::query();

        // Filter berdasarkan status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter berdasarkan pencarian
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        $publications = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.publications.index', compact('publications'));
    }
}