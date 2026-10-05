<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi tidak valid',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user->only(['id', 'username', 'email', 'role']),
                'token' => $token,
            ],
        ]);
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'pelapor',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran akun pelapor berhasil',
            'data' => $user->only(['id', 'username', 'email', 'role']),
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('adminProfile'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    public function index(Request $request)
    {
        $users = $this->indexQuery()->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function pdf()
    {
        $field = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

        $users = $this->indexQuery()->get();

        $rows = '';
        foreach ($users as $user) {
            $rows .= '<tr>
            <td class="num">'.$user->id.'</td>
            <td>'.$field($user->username).'</td>
            <td>'.$field($user->email).'</td>
            <td>'.$field($user->role).'</td>
            <td>'.$field($user->created_at?->format('d M Y H:i')).'</td>
        </tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="5" class="empty">Belum ada user terdaftar.</td></tr>';
        }

        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>
    @page { margin: 18mm 20mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1f2d3d; line-height: 1.45; }
    h1 { font-size: 15pt; margin: 0 0 3px; text-align: center; color: #2b3a55; }
    .meta { text-align: center; font-size: 9pt; color: #6b7c8c; }
    .rule { border-bottom: 2px solid #2b3a55; margin: 10px 0 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #dde3e9; padding: 6px 8px; font-size: 9.5pt; vertical-align: top; }
    th { background-color: #f4f7f9; font-size: 9pt; text-align: left; color: #2b3a55; }
    td.num, th.num { width: 8%; text-align: center; }
    th.role { width: 16%; }
    th.created { width: 20%; }
    td.empty { text-align: center; color: #8a9aa8; font-style: italic; }
</style></head><body>

    <h1>Daftar User</h1>
    <div class="meta">Total: '.$users->count().' &nbsp;|&nbsp; Dicetak: '.now()->format('d M Y H:i').'</div>
    <div class="rule"></div>

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Username</th>
                <th>Email</th>
                <th class="role">Role</th>
                <th class="created">Dibuat</th>
            </tr>
        </thead>
        <tbody>
            '.$rows.'
        </tbody>
    </table>

</body></html>';

        $dompdf = new Dompdf((new Options)->setIsRemoteEnabled(false));
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'daftar-user-'.now()->format('Ymd_His').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"; filename*=UTF-8\'\''.$filename,
        ]);
    }

    private function indexQuery()
    {
        return User::query()
            ->select('id', 'username', 'email', 'role', 'created_at')
            ->orderBy('created_at', 'desc');
    }

    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user->only(['id', 'username', 'email', 'role', 'created_at']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        // Super admin can update anyone; admin cannot touch super_admin
        $auth = $request->user();
        if ($user->role === 'super_admin' && $auth->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak boleh mengubah super_admin',
            ], 403);
        }

        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User diperbarui',
            'data' => $user->only(['id', 'username', 'email', 'role']),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $auth = $request->user();

        // Tidak boleh hapus diri sendiri
        if ($user->id === $auth->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak boleh menghapus akun sendiri',
            ], 422);
        }

        // Admin tidak boleh hapus super_admin
        if ($user->role === 'super_admin' && $auth->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak boleh menghapus super_admin',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User dihapus',
        ]);
    }
}
