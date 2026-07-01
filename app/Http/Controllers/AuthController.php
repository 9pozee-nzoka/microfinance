<?php

namespace App\Http\Controllers;

use App\Mail\LoginOtpMail;
use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const OTP_EXPIRY_MINUTES = 10;
    private const MAX_ATTEMPTS       = 5;   // per minute per IP

    // ── Step 1: Show login form ──────────────────────────────────
    public function showLogin()
    {
        return view('auth.login');
    }

    // ── Step 2: Validate credentials → send OTP ─────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate-limit login attempts per IP
        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Too many attempts. Please wait {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['email' => 'Invalid email or password.']);
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'email' => 'Your account is ' . $user->status . '. Contact your administrator.',
            ]);
        }

        RateLimiter::clear($key);

        // Generate and send OTP
        $this->sendOtp($user, $request->ip());

        // Stash user ID in session (not logged in yet)
        $request->session()->put('2fa_user_id', $user->id);
        $request->session()->put('2fa_intended', $request->session()->get('url.intended', '/dashboard'));

        return redirect()->route('otp.show');
    }

    // ── Step 3: Show OTP form ────────────────────────────────────
    public function showOtp(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('2fa_user_id'));
        if (!$user) {
            return redirect()->route('login');
        }

        $maskedEmail = $this->maskEmail($user->email);
        return view('auth.otp', compact('maskedEmail'));
    }

    // ── Step 4: Verify OTP → complete login ─────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        $userId = $request->session()->get('2fa_user_id');

        // Rate-limit OTP guesses per user
        $key = 'otp:' . $userId;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $request->session()->forget(['2fa_user_id', '2fa_intended']);
            return redirect()->route('login')
                ->withErrors(['email' => 'Too many incorrect attempts. Please log in again.']);
        }

        $otp = LoginOtp::where('user_id', $userId)
            ->where('otp', $request->otp)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['otp' => 'Invalid or expired code. Please try again.'])
                ->with('error', 'Invalid or expired code. Please try again.');
        }

        // Mark OTP used
        $otp->update(['used' => true]);
        RateLimiter::clear($key);

        $user = User::findOrFail($userId);

        // Clean up session keys
        $intended = $request->session()->pull('2fa_intended', '/dashboard');
        $request->session()->forget('2fa_user_id');

        // Log the user in
        auth()->login($user, false);

        // Single-session: invalidate any previous session for this user
        if ($user->session_id) {
            DB::table('sessions')->where('id', $user->session_id)->delete();
        }

        $user->update([
            'session_id'          => session()->getId(),
            'session_started_at'  => now(),
            'last_login_at'       => now(),
            'last_login_ip'       => $request->ip(),
        ]);

        // Redirect customers to portal
        if ($user->hasRole('customer')) {
            return redirect()->route('portal.dashboard');
        }

        return redirect($intended);
    }

    // ── Step 4b: Verify via magic link token (email button) ─────
    public function verifyByToken(Request $request, string $token)
    {
        $otp = LoginOtp::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This verification link has expired or already been used. Please log in again.']);
        }

        // Mark OTP used immediately (prevent replay)
        $otp->update(['used' => true]);

        $user = User::findOrFail($otp->user_id);

        if ($user->status !== 'active') {
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is ' . $user->status . '. Contact your administrator.']);
        }

        // Log the user in
        auth()->login($user, false);

        // Single-session: invalidate any previous session
        if ($user->session_id) {
            DB::table('sessions')->where('id', $user->session_id)->delete();
        }

        $user->update([
            'session_id'         => session()->getId(),
            'session_started_at' => now(),
            'last_login_at'      => now(),
            'last_login_ip'      => $request->ip(),
        ]);

        // Also clean up any stale 2fa session keys
        $request->session()->forget(['2fa_user_id', '2fa_intended']);

        if ($user->hasRole('customer')) {
            return redirect()->route('portal.dashboard');
        }

        return redirect()->route('dashboard')
            ->with('success', 'You have been verified and logged in successfully.');
    }

    // ── Resend OTP ───────────────────────────────────────────────
    public function resendOtp(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $key = 'otp_resend:' . $request->session()->get('2fa_user_id');
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->with('error', 'Too many resend requests. Please wait a minute.');
        }
        RateLimiter::hit($key, 60);

        $user = User::find($request->session()->get('2fa_user_id'));
        if ($user) {
            $this->sendOtp($user, $request->ip());
        }

        return back()->with('success', 'A new code has been sent to your email.');
    }

    // ── Logout ───────────────────────────────────────────────────
    public function logout(Request $request)
    {
        if (auth()->check()) {
            auth()->user()->update(['session_id' => null, 'session_started_at' => null]);
        }
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ── Private helpers ──────────────────────────────────────────

    private function sendOtp(User $user, ?string $ip): void
    {
        // Invalidate all previous unused OTPs for this user
        LoginOtp::where('user_id', $user->id)->where('used', false)->update(['used' => true]);

        $code  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(64);

        $otp = LoginOtp::create([
            'user_id'    => $user->id,
            'otp'        => $code,
            'token'      => $token,
            'ip_address' => $ip,
            'used'       => false,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        $verifyUrl = route('otp.verify.token', ['token' => $token]);

        try {
            Mail::to($user->email)->send(
                new LoginOtpMail($user, $code, self::OTP_EXPIRY_MINUTES, $verifyUrl)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        return $masked . '@' . $domain;
    }
}
