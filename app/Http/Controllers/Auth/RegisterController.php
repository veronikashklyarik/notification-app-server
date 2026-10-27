<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\DeletedAccountService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request, DeletedAccountService $deletedAccounts): RedirectResponse
    {
        // Validation already turned away restorable accounts; this erases an expired one holding the email.
        $deletedAccounts->findRestorable('email', $request->validated('email'));

        $user = User::query()->create(array_merge(
            $request->validated(),
            [
                'timezone' => $request->validated('timezone') ?? 'UTC',
                'locale' => $request->cookie('app_locale') ?? $request->session()->get('locale') ?? config('app.locale', 'en'),
            ],
        ));

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
