<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RestoreMethod;
use App\Http\Controllers\Controller;
use App\Services\DeletedAccountService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    /**
     * Show the password reset form.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'token' => $request->route('token'),
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request, DeletedAccountService $deletedAccounts): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function ($user, $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PasswordReset) {
            return redirect()->route('login')->with('status', __('Your password has been reset. Please sign in.'));
        }

        if ($status === Password::InvalidUser) {
            $deletedUser = $deletedAccounts->findRestorable('email', $request->string('email')->toString());

            if ($deletedUser && Password::tokenExists($deletedUser, $request->string('token')->toString())) {
                $deletedUser->forceFill([
                    'password' => Hash::make($request->string('password')->toString()),
                    'remember_token' => Str::random(60),
                ])->save();
                Password::deleteToken($deletedUser);
                $deletedAccounts->restore($deletedUser, RestoreMethod::PasswordReset);

                event(new PasswordReset($deletedUser));

                return redirect()->route('login')->with('status', __('Your password has been reset and your account restored. Please sign in.'));
            }

            $status = Password::InvalidToken;
        }

        return back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
    }
}
