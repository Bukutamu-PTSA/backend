<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Daftar user internal (Manajemen User). Mendukung pencarian & filter role.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.mb_strtolower((string) $request->string('search')).'%';
                $query->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(full_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->orderBy('full_name')
            ->paginate(15);

        return UserResource::collection($users)->response();
    }

    /**
     * Simpan user internal baru.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] ??= 'aktif';

        $user = User::create($validated);

        return (new UserResource($user))
            ->additional(['success' => true, 'message' => 'User berhasil ditambahkan'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Tampilkan detail user.
     */
    public function show(User $user): JsonResponse
    {
        return (new UserResource($user))->response();
    }

    /**
     * Perbarui user. Password hanya diubah bila diisi.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return (new UserResource($user->refresh()))
            ->additional(['success' => true, 'message' => 'User berhasil diperbarui'])
            ->response();
    }

    /**
     * Hapus user. Cegah menghapus akun sendiri.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()?->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun sendiri.',
            ], 409);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User dihapus',
        ]);
    }
}
