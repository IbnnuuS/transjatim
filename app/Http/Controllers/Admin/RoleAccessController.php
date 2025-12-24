<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class RoleAccessController extends Controller
{
    /**
     * Tampilkan daftar user + filter + ringkasan.
     */
    public function index(Request $request)
    {
        $q    = (string) $request->query('q', '');
        $role = (string) $request->query('role', '');

        $searchable = ['name', 'email', 'username'];

        $users = User::query()
            ->when($q, function ($qq) use ($q, $searchable) {
                $qq->where(function ($w) use ($q, $searchable) {
                    foreach ($searchable as $i => $col) {
                        $pattern = "%{$q}%";
                        $i === 0
                            ? $w->where($col, 'like', $pattern)
                            : $w->orWhere($col, 'like', $pattern);
                    }
                });
            })
            ->when($role, fn($qq) => $qq->where('role', $role))
            ->orderBy('name')
            ->paginate(10);

        $countAdmin = User::where('role', 'admin')->count();
        $countUser  = User::where('role', 'user')->count();

        return view('admin.rolesAdmin.rolesAdmin', compact(
            'users',
            'countAdmin',
            'countUser'
        ));
    }

    /**
     * Buat user baru.
     */
    public function store(Request $request)
    {
        // ✅ Explicit Admin Check
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'role'     => $data['role'],
        ]);

        return back()->with('success', "User {$user->name} berhasil dibuat.");
    }

    /**
     * ✅ Update role user (FIX PATCH)
     */
    public function updateRole(Request $request, User $user)
    {
        // ✅ Explicit Admin Check
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'role' => 'required|in:admin,user',
        ]);

        $user->update([
            'role' => $data['role']
        ]);

        return back()->with('success', "Role {$user->name} diubah menjadi {$data['role']}.");
    }

    /**
     * Hapus user.
     */
    public function destroy(User $user)
    {
        // ✅ Explicit Admin Check
        abort_unless(auth()->user()->isAdmin(), 403);

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "User {$name} berhasil dihapus.");
    }
}
