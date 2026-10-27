<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\DeletedAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, DeletedAccountService $deletedAccounts): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->has('remember'))) {
            $deletedUser = $deletedAccounts->findRestorable('email', $request->string('email')->toString());

            if (! $deletedUser?->password || ! Hash::check($request->string('password')->toString(), $deletedUser->password)) {
                return back()->withErrors([
                    'email' => __('Email or password is wrong.'),
                ])->onlyInput('email');
            }

            $deletedAccounts->restore($deletedUser);
            Auth::login($deletedUser, $request->has('remember'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
