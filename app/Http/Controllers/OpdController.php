<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OpdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Ambil semua data OPD
            $opdList = Opd::orderBy('name', 'asc')->get();
            
            // Hitung statistik
            $stats = [
                'total' => $opdList->count(),
                'dengan_logo' => $opdList->whereNotNull('logo')->count(),
                'dengan_email' => $opdList->whereNotNull('email')->count(),
                'dengan_telepon' => $opdList->whereNotNull('telepon')->count(),
            ];

            return view('backend.pages.aspirasi.opd', compact('opdList', 'stats'));
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memuat data OPD: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'singkatan' => 'required|string|max:20|unique:opd,singkatan',
                'telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // 2MB max
            ], [
                'name.required' => 'Nama OPD wajib diisi',
                'name.max' => 'Nama OPD maksimal 255 karakter',
                'singkatan.required' => 'Singkatan wajib diisi',
                'singkatan.max' => 'Singkatan maksimal 20 karakter',
                'singkatan.unique' => 'Singkatan sudah digunakan, pilih yang lain',
                'telepon.max' => 'Nomor telepon maksimal 20 karakter',
                'email.email' => 'Format email tidak valid',
                'email.max' => 'Email maksimal 255 karakter',
                'logo.image' => 'File harus berupa gambar',
                'logo.mimes' => 'Logo harus berformat: jpeg, png, jpg, gif',
                'logo.max' => 'Ukuran logo maksimal 2MB'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Siapkan data untuk disimpan
            $data = [
                'name' => $request->name,
                'singkatan' => strtoupper($request->singkatan),
                'telepon' => $request->telepon,
                'email' => $request->email,
            ];

            // Handle upload logo
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                $logoName = time() . '_' . $request->singkatan . '.' . $logoFile->getClientOriginalExtension();
                $logoPath = $logoFile->storeAs('opd/logos', $logoName, 'public');
                $data['logo'] = $logoPath;
            }

            // Simpan data
            $opd = Opd::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Data OPD berhasil ditambahkan',
                'data' => $opd
            ]);

        } catch (\Exception $e) {
            // Hapus file logo jika gagal menyimpan data
            if (isset($logoPath) && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $opd = Opd::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $opd
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data OPD tidak ditemukan'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Cari data OPD
            $opd = Opd::findOrFail($id);
            
            // Validasi input
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'singkatan' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('opd', 'singkatan')->ignore($opd->id)
                ],
                'telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ], [
                'name.required' => 'Nama OPD wajib diisi',
                'name.max' => 'Nama OPD maksimal 255 karakter',
                'singkatan.required' => 'Singkatan wajib diisi',
                'singkatan.max' => 'Singkatan maksimal 20 karakter',
                'singkatan.unique' => 'Singkatan sudah digunakan, pilih yang lain',
                'telepon.max' => 'Nomor telepon maksimal 20 karakter',
                'email.email' => 'Format email tidak valid',
                'email.max' => 'Email maksimal 255 karakter',
                'logo.image' => 'File harus berupa gambar',
                'logo.mimes' => 'Logo harus berformat: jpeg, png, jpg, gif',
                'logo.max' => 'Ukuran logo maksimal 2MB'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Siapkan data untuk diupdate
            $data = [
                'name' => $request->name,
                'singkatan' => strtoupper($request->singkatan),
                'telepon' => $request->telepon,
                'email' => $request->email,
            ];

            // Handle upload logo baru
            if ($request->hasFile('logo')) {
                // Hapus logo lama jika ada
                if ($opd->logo && Storage::disk('public')->exists($opd->logo)) {
                    Storage::disk('public')->delete($opd->logo);
                }

                // Upload logo baru
                $logoFile = $request->file('logo');
                $logoName = time() . '_' . $request->singkatan . '.' . $logoFile->getClientOriginalExtension();
                $logoPath = $logoFile->storeAs('opd/logos', $logoName, 'public');
                $data['logo'] = $logoPath;
            }

            // Update data
            $opd->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Data OPD berhasil diperbarui',
                'data' => $opd->fresh()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data OPD tidak ditemukan'
            ], 404);
            
        } catch (\Exception $e) {
            // Hapus file logo baru jika gagal update
            if (isset($logoPath) && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $opd = Opd::findOrFail($id);

        // Cek relasi dengan kategori aspirasi
        if ($opd->kategoriAspirasi()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'OPD tidak dapat dihapus karena masih digunakan pada kategori aspirasi.'
            ], 400);
        }

        // Hapus logo jika ada
        if ($opd->logo && Storage::disk('public')->exists($opd->logo)) {
            Storage::disk('public')->delete($opd->logo);
        }

        // Hapus data OPD
        $opd->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data OPD berhasil dihapus.'
        ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data OPD tidak ditemukan'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get OPD list for select options (API endpoint)
     */
    public function getOpdList()
    {
        try {
            $opdList = Opd::select('id', 'name', 'singkatan')
                          ->orderBy('name', 'asc')
                          ->get();

            return response()->json([
                'success' => true,
                'data' => $opdList
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat daftar OPD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search OPD by name or singkatan
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');
            
            $opdList = Opd::where('name', 'LIKE', "%{$query}%")
                          ->orWhere('singkatan', 'LIKE', "%{$query}%")
                          ->orderBy('name', 'asc')
                          ->limit(10)
                          ->get(['id', 'name', 'singkatan']);

            return response()->json([
                'success' => true,
                'data' => $opdList
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari data OPD: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get OPD statistics
     */
    public function getStats()
    {
        try {
            $stats = [
                'total' => Opd::count(),
                'dengan_logo' => Opd::whereNotNull('logo')->count(),
                'dengan_email' => Opd::whereNotNull('email')->count(),
                'dengan_telepon' => Opd::whereNotNull('telepon')->count(),
                'tanpa_kontak' => Opd::whereNull('telepon')->whereNull('email')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik: ' . $e->getMessage()
            ], 500);
        }
    }
}