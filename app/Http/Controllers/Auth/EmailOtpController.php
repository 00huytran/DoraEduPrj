<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationOtpMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmailOtpController extends Controller
{
    /**
     * Show OTP page if a pending registration exists.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        if (! Session::has('pending_registration')) {
            return redirect()->route('register');
        }

        $pending = Session::get('pending_registration');
        $lastSentAt = $pending['otp']['last_sent_at'] ?? null;
        $cooldownSeconds = 300;
        $remaining = 0;
        if ($lastSentAt) {
            $elapsed = now()->timestamp - (int) $lastSentAt;
            $remaining = max(0, $cooldownSeconds - $elapsed);
        }

        return Inertia::render('auth/EmailOtp', [
            'email' => $pending['email'],
            'resendCooldownSeconds' => $remaining,
        ]);
    }

    /**
     * Send or resend the OTP to the pending registration email.
     */
    public function send(Request $request): RedirectResponse
    {
        $pending = Session::get('pending_registration');
        if (! $pending) {
            return redirect()->route('register');
        }

        $now = now()->timestamp;
        $cooldownSeconds = 300;
        $lastSentAt = $pending['otp']['last_sent_at'] ?? null;
        if ($lastSentAt && ($now - (int) $lastSentAt) < $cooldownSeconds) {
            $remaining = $cooldownSeconds - ($now - (int) $lastSentAt);
            return redirect()->route('auth.email-otp')->with('status', 'otp-throttled')->with('resendCooldownRemaining', $remaining);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10)->timestamp;

        $pending['otp'] = [
            'code' => $otp,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'last_sent_at' => $now,
        ];

        Session::put('pending_registration', $pending);

        Mail::to($pending['email'])->send(new RegistrationOtpMail($otp));

        return redirect()->route('auth.email-otp')->with('status', 'otp-sent');
    }

    /**
     * Verify the submitted OTP and finalize registration.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pending = Session::get('pending_registration');
        if (! $pending || empty($pending['otp'])) {
            return redirect()->route('register');
        }

        $otp = $pending['otp'];
        $otp['attempts'] = ($otp['attempts'] ?? 0) + 1;

        if ($otp['attempts'] > 5) {
            Session::forget('pending_registration');
            return redirect()->route('register')->withErrors(['email' => 'Vượt quá số lần thử OTP. Vui lòng đăng ký lại.']);
        }

        if (now()->timestamp > ($otp['expires_at'] ?? 0)) {
            $pending['otp'] = $otp;
            Session::put('pending_registration', $pending);
            return back()->withErrors(['otp' => 'Mã OTP đã hết hạn. Hãy gửi lại.']);
        }

        $submitted = (string) $request->input('otp');
        if ($submitted !== ($otp['code'] ?? '')) {
            $pending['otp'] = $otp;
            Session::put('pending_registration', $pending);
            return back()->withErrors(['otp' => 'Mã OTP không đúng.']);
        }

        // OTP valid -> create user and login
        $controller = app(RegisteredUserFinalizer::class);
        return $controller($request);
    }
}

