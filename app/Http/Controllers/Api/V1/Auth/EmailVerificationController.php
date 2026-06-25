<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ApiEmailVerificationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailVerificationController extends Controller
{
    /**
     * Resend Email Verification
     *
     * Sends (or re-sends) an email verification link to the authenticated user.
     * Returns immediately if the email is already verified.
     */
    public function send(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $request->user()->notify(new ApiEmailVerificationNotification);

        return response()->json(['message' => 'Verification email sent.']);
    }

    /**
     * Verify Email
     *
     * Validates the signed verification URL from the email and marks the user's
     * email as verified. Intended to be opened in a browser after clicking the
     * link in the verification email.
     *
     * @unauthenticated
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse|Response
    {
        if (! $request->hasValidSignature()) {
            return redirect(config('app.deeplink_scheme').'://email-verification-failed?reason=expired');
        }

        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect(config('app.deeplink_scheme').'://email-verification-failed?reason=invalid');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect(config('app.deeplink_scheme').'://email-verified');
    }
}
