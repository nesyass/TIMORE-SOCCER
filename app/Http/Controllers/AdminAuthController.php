<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends \App\Http\Controllers\Controller
{
    public function showLogin(Request $request)
    {
        if ($request->user('web')?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        if (Auth::guard('web')->attempt($credentials + ['is_admin' => true])) {
            $request->session()->regenerate();
            return redirect()->to($request->session()->pull('dashboard_admin.intended', route('admin.dashboard')));
        }
        return back()->withErrors(['email'=>'Email atau password tidak sesuai.'])->onlyInput('email');
    }
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();


        return redirect()->route('admin.login');
    }
}
