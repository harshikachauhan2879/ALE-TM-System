<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if ($user->status === 'inactive') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is inactive.',
                ]);
            }

            $request->session()->regenerate();
            ActivityLog::log('login', "User logged in: {$user->name} ({$user->role})");

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:employee,client,team_lead',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
            'status' => 'active',
        ]);

        Auth::login($user);
        ActivityLog::log('register', "User registered: {$user->name} ({$user->role})");

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLog::log('logout', "User logged out: " . Auth::user()->name);
        }
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function apiTokens()
    {
        $tokens = Auth::user()->tokens;
        return view('dashboard.tokens', compact('tokens'));
    }

    public function generateToken(Request $request)
    {
        $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $token = Auth::user()->createToken($request->token_name);
        ActivityLog::log('token_generate', "Generated API Token: {$request->token_name}");

        return back()->with('success_token', $token->plainTextToken);
    }

    public function deleteToken($id)
    {
        $token = Auth::user()->tokens()->findOrFail($id);
        $token->delete();
        ActivityLog::log('token_delete', "Deleted API Token: {$token->name}");

        return back()->with('success', 'Token deleted successfully.');
    }
}
