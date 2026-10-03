<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email', 'max:255']]);
        Password::sendResetLink($credentials);

        return back()->with('status', 'Jika email terdaftar, tautan pemulihan password akan dikirim. Periksa kotak masuk emailmu.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($credentials, function (User $user, string $password) use ($audit): void {
            $user->password = $password;
            $user->setRememberToken(Str::random(60));
            $user->save();
            $audit->log('auth.password_reset', 'Password akun dipulihkan.', $user, user: $user);
            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', 'Password berhasil diperbarui. Silakan masuk kembali.')
            : back()->withErrors(['email' => 'Tautan pemulihan tidak valid atau sudah kedaluwarsa. Minta tautan baru.']);
    }
}
