<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form or redirect authenticated user.
     */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('marketlink.index');
        }

        return view('auth.login');
    }

    /**
     * Handle an authentication attempt with rate limiting and audit logging.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            SecurityAuditLog::logViolation(
                'auth_lockout',
                "هەوڵی زۆری چوونەژوورەوەی شکستخواردوو بۆ ئەم هەژمارە ({$request->input('email')}). بلۆک کراوە بۆ ماوەی {$seconds} چرکە.",
                $request,
                null,
                null,
                'critical',
                ['email' => $request->input('email'), 'available_in_seconds' => $seconds]
            );

            throw ValidationException::withMessages([
                'email' => [__('auth.throttle', ['seconds' => $seconds]) ?: "هەوڵی زۆر دراوە! تکایە پاش {$seconds} چرکەی تر تاقی بکەرەوە."],
            ]);
        }

        // Check if user exists and is active
        $user = User::where('email', $request->input('email'))->first();

        if ($user && !$user->is_active) {
            RateLimiter::hit($throttleKey, 60);

            SecurityAuditLog::logViolation(
                'auth_inactive_account',
                "هەوڵی چوونەژوورەوە بۆ هەژماری ناچالاککراو: {$user->email}",
                $request,
                $user->company_id,
                $user->id,
                'warning'
            );

            throw ValidationException::withMessages([
                'email' => ['ئەم هەژمارە ناچالاک کراوە. تکایە پەیوەندی بە بەڕێوەبەری سیستەمەوە بکە.'],
            ]);
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $authenticatedUser = Auth::user();

            SecurityAuditLog::logViolation(
                'auth_login_success',
                "چوونەژوورەوەی سەرکەوتوو بۆ بەکارهێنەر: {$authenticatedUser->name} ({$authenticatedUser->email}) بە ڕۆڵی: {$authenticatedUser->role}",
                $request,
                $authenticatedUser->company_id,
                $authenticatedUser->id,
                'info'
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'چوونەژوورەوە سەرکەوتوو بوو.',
                    'user' => [
                        'id' => $authenticatedUser->id,
                        'name' => $authenticatedUser->name,
                        'email' => $authenticatedUser->email,
                        'role' => $authenticatedUser->role,
                        'company_id' => $authenticatedUser->company_id,
                    ],
                ]);
            }

            // Route based on role
            if ($authenticatedUser->isPlatformAdmin()) {
                session(['active_role' => 'operator']);
                return redirect()->intended(route('marketlink.index', ['role' => 'operator']));
            } elseif ($authenticatedUser->isDriver()) {
                session(['active_role' => 'driver']);
                return redirect()->intended(route('marketlink.index', ['role' => 'driver']));
            } elseif ($authenticatedUser->company) {
                $role = $authenticatedUser->company->business_type === 'supplier' ? 'supplier' : 'retailer';
                session(['active_role' => $role]);
                if ($role === 'supplier') {
                    session(['active_supplier_id' => $authenticatedUser->company_id]);
                } else {
                    session(['active_retailer_id' => $authenticatedUser->company_id]);
                }
                return redirect()->intended(route('marketlink.index', ['role' => $role]));
            }

            return redirect()->intended(route('marketlink.index'));
        }

        RateLimiter::hit($throttleKey, 60);

        SecurityAuditLog::logViolation(
            'auth_failed_login',
            "هەوڵی چوونەژوورەوەی سەرنەکەوتوو بە ئیمەیڵی: {$request->input('email')}",
            $request,
            null,
            null,
            'warning',
            ['email' => $request->input('email')]
        );

        throw ValidationException::withMessages([
            'email' => ['ئیمەیڵ یان وشەی نهێنی نادروستە.'],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            SecurityAuditLog::logViolation(
                'auth_logout',
                "دەرچوونی بەکارهێنەر لە سیستەم: {$user->name} ({$user->email})",
                $request,
                $user->company_id,
                $user->id,
                'info'
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'بە سەرکەوتوویی چووە دەرەوە.',
            ]);
        }

        return redirect()->route('marketlink.index');
    }
}
