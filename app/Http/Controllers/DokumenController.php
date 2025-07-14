<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dokumen;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        $dokumens = Dokumen::all();
        return view('backend.pages.dokumen.index', compact('dokumens'));
    }

    // Store a newly created resource in storage.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240', // max 10MB
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('dokumen_files', 'public');
            $validated['file'] = $path;
        }

        $dokumen = Dokumen::create($validated);
        return redirect()->route('dokumen.index')->with('success', 'Dokumen berhasil di tambahkan.');
    }


    // Update the specified resource in storage.
    public function update(Request $request, $id)
    {
        $dokumen = Dokumen::find($id);
        if (!$dokumen) {
            return redirect()->route('dokumen.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'file' => 'sometimes|required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240', // max 10MB
        ]);

        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($dokumen->file && Storage::disk('public')->exists($dokumen->file)) {
                Storage::disk('public')->delete($dokumen->file);
            }
            $path = $request->file('file')->store('dokumen_files', 'public');
            $validated['file'] = $path;
        }

        $dokumen->update($validated);
        return redirect()->route('dokumen.index')->with('success', 'Dokumen berhasil diubah.');
    }

    // Remove the specified resource from storage.
    public function destroy($id)
    {
        $dokumen = Dokumen::find($id);
        if (!$dokumen) {
            return redirect()->route('dokumen.index')->with('error', 'Dokumen tidak ditemukan.');
        }

        // Delete file from storage
        if ($dokumen->file && Storage::disk('public')->exists($dokumen->file)) {
            Storage::disk('public')->delete($dokumen->file);
        }

        $dokumen->delete();
        return redirect()->route('dokumen.index')->with('success', 'Dokumen berhasil dihapus.');
    }
}
