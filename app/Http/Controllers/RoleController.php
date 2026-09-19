<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Slug yang menjadi ketergantungan langsung kode aplikasi (middleware,
     * pengecekan role, dan provisioning login Google) sehingga tidak boleh
     * dihapus atau diubah melalui menu manajemen role.
     */
    private const RESERVED_SLUGS = ['super-admin', 'admin-bappeda', 'admin-opd', 'user'];

    public function index()
    {
        $roleList = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

        $stats = [
            'total' => $roleList->count(),
            'aktif' => $roleList->where('is_active', true)->count(),
            'nonaktif' => $roleList->where('is_active', false)->count(),
        ];

        return view('backend.pages.roles.index', compact('roleList', 'stats'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:roles,name',
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'unique:roles,slug'],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama role harus diisi',
            'name.unique' => 'Nama role sudah digunakan',
            'slug.required' => 'Slug harus diisi',
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung (contoh: admin-sektor)',
            'slug.unique' => 'Slug sudah digunakan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil ditambahkan',
            'data' => $role,
        ]);
    }

    public function show(Role $role)
    {
        return response()->json([
            'success' => true,
            'data' => $role->loadCount('users'),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama role harus diisi',
            'name.unique' => 'Nama role sudah digunakan',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Slug tidak diubah lewat form: kode aplikasi mengandalkan slug yang stabil.
        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diperbarui',
            'data' => $role->fresh(),
        ]);
    }

    public function destroy(Role $role)
    {
        if (in_array($role->slug, self::RESERVED_SLUGS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Role sistem ini tidak dapat dihapus.',
            ], 403);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak dapat dihapus karena masih digunakan oleh user.',
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus',
        ]);
    }

    /**
     * Daftar permission per modul beserta status centang untuk role ini.
     */
    public function permissions(Role $role)
    {
        $granted = $role->permissions()->pluck('name')->all();

        $modules = [];
        foreach (Permission::CATALOG as $module => $definition) {
            $actions = [];
            foreach ($definition['actions'] as $action => $label) {
                $name = "{$module}.{$action}";
                $actions[] = [
                    'name' => $name,
                    'label' => $label,
                    'granted' => in_array($name, $granted, true),
                ];
            }

            $modules[] = ['module' => $module, 'label' => $definition['label'], 'actions' => $actions];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $role->only(['id', 'name', 'slug']),
                'locked' => $role->slug === 'super-admin',
                'modules' => $modules,
            ],
        ]);
    }

    /**
     * Simpan hak akses (permission) untuk sebuah role.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        if ($role->slug === 'super-admin') {
            return response()->json([
                'success' => false,
                'message' => 'Super Admin selalu memiliki seluruh hak akses dan tidak dapat diubah.',
            ], 403);
        }

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(Permission::catalogNames())],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Hak akses role berhasil diperbarui',
        ]);
    }
}
