<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\RegistrationOtpMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Defer user creation until OTP verification
        $pending = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password_hash' => Hash::make($request->string('password')->toString()),
        ];

        session(['pending_registration' => $pending]);

        // Trigger initial OTP send server-side, then redirect to OTP page (GET)
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = now()->timestamp;
        $expiresAt = now()->addMinutes(10)->timestamp;

        $pending['otp'] = [
            'code' => $otp,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'last_sent_at' => $now,
        ];

        session(['pending_registration' => $pending]);

        Mail::to($pending['email'])->send(new RegistrationOtpMail($otp));

        return redirect()->route('auth.email-otp')->with('status', 'otp-sent');
    }
}
