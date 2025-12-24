<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Jobdesk;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('user.profile.profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $isChangePassword = $request->get('action') === 'change_password';

        $rules = [
            'name'       => 'required|string|max:255',
            'full_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'birth_date' => 'nullable|date',

            // ✅ divisi baru
            'division'   => 'nullable|in:Teknik,Digital,Customer Service',

            'avatar'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        if ($isChangePassword || $request->filled('password')) {
            $rules['current_password'] = 'required';
            $rules['password'] = 'required|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($request, $user, $validated, $isChangePassword) {

            $oldDivision = $user->division ?? $user->divisi ?? null;
            $newDivision = $validated['division'] ?? null;

            // ✅ Update field profil
            $user->name       = $validated['name'];
            $user->full_name  = $validated['full_name'];
            $user->email      = $validated['email'];
            $user->birth_date = $validated['birth_date'] ?? null;

            // ✅ Sinkron user.division & user.divisi (jaga kompatibilitas)
            if ($newDivision !== null) {
                if (Schema::hasColumn('users', 'division')) $user->division = $newDivision;
                if (Schema::hasColumn('users', 'divisi'))   $user->divisi   = $newDivision;
            }

            // ✅ Upload avatar
            if ($request->hasFile('avatar')) {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $user->avatar = $request->file('avatar')->store('avatars', 'public');
            }

            // ✅ ganti password
            if ($isChangePassword || $request->filled('password')) {
                if (!Hash::check($request->input('current_password'), $user->password)) {
                    throw new \Exception('Password sekarang tidak sesuai.');
                }
                $user->password = Hash::make($request->input('password'));
            }

            $user->save();

            // ✅ Jika divisi berubah, update semua jobdesk user ini
            if ($newDivision !== null && $oldDivision !== $newDivision) {
                $jobdeskQuery = Jobdesk::where('user_id', $user->id);

                if (Schema::hasColumn('jobdesks', 'division')) {
                    $jobdeskQuery->update(['division' => $newDivision]);
                }

                if (Schema::hasColumn('jobdesks', 'divisi')) {
                    $jobdeskQuery->update(['divisi' => $newDivision]);
                }
            }
        });

        $msg = $isChangePassword ? 'Password berhasil diperbarui.' : 'Profil berhasil diperbarui.';
        if (!$isChangePassword && $request->filled('password')) {
            $msg = 'Profil & password berhasil diperbarui.';
        }

        return back()->with('success', $msg);
    }
}
