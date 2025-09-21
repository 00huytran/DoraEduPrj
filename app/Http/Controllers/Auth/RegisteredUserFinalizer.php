<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RegisteredUserFinalizer extends Controller
{
    /**
     * Finalize registration from session and log the user in.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $pending = Session::get('pending_registration');
        if (! $pending) {
            return redirect()->route('register');
        }

        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => $pending['password_hash'],
        ]);

        event(new Registered($user));

        Session::forget('pending_registration');

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}


