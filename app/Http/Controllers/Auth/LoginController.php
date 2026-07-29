<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoginLog;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Check if user account is suspended
            if (!$user->is_active) {
                LoginLog::create([
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'email' => $user->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status' => 'blocked_suspended',
                ]);

                return back()->withErrors([
                    'email' => 'Your account is suspended. Please contact system administrator.',
                ])->onlyInput('email');
            }

            // Check shift restriction (Super Admin bypasses shift restriction)
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

                    return back()->withErrors([
                        'email' => "Login denied. Your shift ({$user->shift->formatted_shift}) is not active right now.",
                    ])->onlyInput('email');
                }
            }
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $authUser = Auth::user();

            // Set single active session ID
            $authUser->update([
                'session_id' => $request->session()->getId(),
                'last_activity' => now(),
            ]);

            // Log successful login
            LoginLog::create([
                'user_id' => $authUser->id,
                'user_name' => $authUser->name,
                'email' => $authUser->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'success',
                'logged_in_at' => now(),
            ]);

            $intended = session('url.intended');
            if ($intended && (str_contains($intended, '/notifications') || str_contains($intended, '/preview-pallets') || str_contains($intended, '/batches'))) {
                session()->forget('url.intended');
            }

            return redirect()->intended(route('dashboard'));
        }

        // Log failed attempt
        LoginLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'email' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'failed_credentials',
        ]);

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            LoginLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'logout',
                'logged_out_at' => now(),
            ]);

            $user->update(['session_id' => null]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
