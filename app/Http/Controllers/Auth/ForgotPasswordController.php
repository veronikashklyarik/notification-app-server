<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DeletedAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a password reset link to the given user.
     */
    public function store(Request $request, DeletedAccountService $deletedAccounts): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        // A recently deleted account gets a link too: setting a new password restores it. The answer stays
        // the same either way, so the page never reveals whether an account exists or was deleted.
        if ($status === Password::InvalidUser) {
            $deletedUser = $deletedAccounts->findRestorable('email', $request->string('email')->toString());

            if ($deletedUser && ! Password::getRepository()->recentlyCreatedToken($deletedUser)) {
                $deletedUser->sendPasswordResetNotification(Password::createToken($deletedUser));
            }
        }

        return back()->withInput()->with('status', __('If an account with that email exists, a password reset link has been sent.'));
    }
}
