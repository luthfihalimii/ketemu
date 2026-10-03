<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, AuditLogger $audit): RedirectResponse
    {
        // Role is never taken from the request; every new account is a student.
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        $audit->log('auth.registered', 'Akun mahasiswa baru terdaftar.', $user);

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice')
            ->with('status', 'Akun berhasil dibuat. Periksa email kampusmu untuk tautan verifikasi.');
    }
}
