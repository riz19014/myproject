<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /** Fixed login OTP (6 digits). */
    public const LOGIN_OTP = '099877';

    private const OTP_PENDING_USER_ID = 'login_otp_pending_user_id';

    private const OTP_PENDING_EMAIL = 'login_otp_pending_email';

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (session()->has(self::OTP_PENDING_USER_ID)) {
            return redirect()->route('login.otp');
        }

        return view('auth.login');
    }

    /**
     * Handle login request — credentials first, then OTP step.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::validate($credentials)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput($request->only('email'));
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active) {
            return back()->withErrors(['email' => 'Your account is inactive.'])->withInput($request->only('email'));
        }

        $request->session()->put(self::OTP_PENDING_USER_ID, $user->id);
        $request->session()->put(self::OTP_PENDING_EMAIL, $user->email);

        return redirect()->route('login.otp');
    }

    /**
     * Show the OTP verification form.
     */
    public function showOtpForm(Request $request)
    {
        if (! $request->session()->has(self::OTP_PENDING_USER_ID)) {
            return redirect()->route('login');
        }

        return view('auth.otp', [
            'email' => $request->session()->get(self::OTP_PENDING_EMAIL),
        ]);
    }

    /**
     * Verify fixed OTP and complete login.
     */
    public function verifyOtp(Request $request)
    {
        if (! $request->session()->has(self::OTP_PENDING_USER_ID)) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Please enter the 6-digit OTP.',
            'otp.digits' => 'OTP must be exactly 6 digits.',
        ]);

        if ($validated['otp'] !== self::LOGIN_OTP) {
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
        }

        $userId = (int) $request->session()->pull(self::OTP_PENDING_USER_ID);
        $request->session()->forget(self::OTP_PENDING_EMAIL);

        $user = User::query()->find($userId);
        if (! $user || ! $user->is_active) {
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive.']);
        }

        Auth::login($user, false);
        $request->session()->regenerate();

        return redirect()->intended(route('users.index'));
    }

    /**
     * Cancel OTP step and return to login.
     */
    public function cancelOtp(Request $request)
    {
        $request->session()->forget([self::OTP_PENDING_USER_ID, self::OTP_PENDING_EMAIL]);

        return redirect()->route('login');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
