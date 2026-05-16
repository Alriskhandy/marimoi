<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class AdminUserController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/users',
        tags: ['Admin Users'],
        summary: 'List users',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(): JsonResponse
    {
        $users = User::with(['role', 'opd'])->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $users]);
    }

    #[OA\Get(
        path: '/api/v1/admin/users/{user}',
        tags: ['Admin Users'],
        summary: 'Detail user',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $user->load(['role', 'opd']),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/users',
        tags: ['Admin Users'],
        summary: 'Buat user baru',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'role_id'],
                properties: [
                    new OA\Property(property: 'name',     type: 'string'),
                    new OA\Property(property: 'email',    type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'role_id',  type: 'integer'),
                    new OA\Property(property: 'opd_id',   type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id'  => 'required|exists:roles,id',
            'opd_id'   => 'nullable|exists:opd,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
            'opd_id'   => $request->opd_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil ditambahkan',
            'data'    => $user->load(['role', 'opd']),
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{user}',
        tags: ['Admin Users'],
        summary: 'Update user',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role_id'  => 'sometimes|required|exists:roles,id',
            'opd_id'   => 'nullable|exists:opd,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'role_id', 'opd_id']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diupdate',
            'data'    => $user->load(['role', 'opd']),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/users/{user}',
        tags: ['Admin Users'],
        summary: 'Hapus user',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function destroy(User $user): JsonResponse
    {
        if ($user->id === 1) {
            return response()->json(['success' => false, 'message' => 'Super Admin tidak dapat dihapus'], 403);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User berhasil dihapus']);
    }
}
