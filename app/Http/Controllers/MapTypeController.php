<?php

namespace App\Http\Controllers;

use App\Models\MapType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MapTypeController extends Controller
{
    public function index()
    {
        $mapTypes = MapType::withCount('spatialLayers')->orderBy('urutan')->get();

        return view('backend.pages.map-types.index', compact('mapTypes'));
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        MapType::create($validator->validated());

        return redirect()->route('map-types.index')->with('success', 'Jenis peta berhasil dibuat');
    }

    public function update(Request $request, MapType $mapType)
    {
        $validator = $this->validator($request, $mapType->id);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $mapType->update($validator->validated());

        return redirect()->route('map-types.index')->with('success', 'Jenis peta berhasil diperbarui');
    }

    public function destroy(MapType $mapType)
    {
        if ($mapType->spatialLayers()->exists()) {
            return redirect()->back()->with('error', 'Jenis peta tidak dapat dihapus karena masih dipakai oleh Layer.');
        }

        $mapType->delete();

        return redirect()->route('map-types.index')->with('success', 'Jenis peta berhasil dihapus');
    }

    private function validator(Request $request, ?int $ignoreId = null)
    {
        return Validator::make($request->all(), [
            'slug' => [
                'required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('map_types', 'slug')->ignore($ignoreId),
            ],
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'urutan' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ], [
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan underscore',
            'slug.unique' => 'Slug sudah dipakai jenis peta lain',
        ]);
    }
}
