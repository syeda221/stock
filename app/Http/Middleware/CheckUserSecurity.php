<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\LoginLog;

class CheckUserSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // 1. Account Suspended Check
            if (!$user->is_active) {
                LoginLog::create([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'email' => $user->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status' => 'blocked_suspended',
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended by the administrator.',
                ]);
            }

            // 2. Single Active Session Check (Super Admin is exempt if needed, but apply for security)
            if ($user->session_id && $user->session_id !== $request->session()->getId()) {
                LoginLog::create([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'email' => $user->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status' => 'single_session_terminated',
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'You were logged out because your account was logged in from another browser/device.',
                ]);
            }

            // 3. Shift Time Check (Super Admin is exempt)
            if (!$user->hasRole('Super Admin') && $user->shift) {
                if (!$user->shift->isWithinShift()) {
                    LoginLog::create([
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'email' => $user->email,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'status' => 'blocked_shift',
                    ]);

                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => "Access denied. Your assigned shift ({$user->shift->formatted_shift}) is currently inactive.",
                    ]);
                }
            }

            // Update last activity timestamp
            $user->updateQuietly(['last_activity' => now()]);
        }

        return $next($request);
    }
}
