<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bawaan Laravel untuk verifikasi email: halaman pengingat, tautan bertanda
 * tangan, dan kirim ulang email verifikasi.
 */
class EmailVerificationController extends Controller
{
    /**
     * Halaman pengingat untuk pengguna yang belum memverifikasi emailnya.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : view('auth.verify-email');
    }

    /**
     * Tandai email terverifikasi lewat tautan bertanda tangan.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('dashboard')
            ->with('status', 'Email berhasil diverifikasi. Selamat datang di KETEMU PENS!');
    }

    /**
     * Kirim ulang email verifikasi.
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Tautan verifikasi baru sudah dikirim ke emailmu.');
    }
}
