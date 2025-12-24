<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan form login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses login + redirect berdasar role.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        // Jika pakai Spatie (pakai middleware role:... di web.php)
        if (method_exists($user, 'hasRole')) {
            $target = $user->hasRole('admin')
                ? route('dashboard.admin')
                : route('dashboard.user');

            return redirect()->intended($target);
        }

        // Cadangan jika tidak pakai Spatie, cek kolom role
        $isAdmin = in_array(strtolower($user->role ?? ''), ['admin']);
        $target  = $isAdmin ? route('dashboard.admin') : route('dashboard.user');

        return redirect()->intended($target);
    }

    /**
     * Logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
