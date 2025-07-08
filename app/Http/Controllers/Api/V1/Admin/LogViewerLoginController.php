<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LogViewerLoginController extends Controller
{
    //
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('log_viewer_guard')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect('/log-viewer');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }
}
