<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteAccountRequest;
use Illuminate\Http\JsonResponse;

class DeleteAccountController extends Controller
{
    /**
     * Delete Account
     *
     * Permanently deletes the authenticated user's account. Requires password confirmation.
     * Deletes the user, revokes all tokens, removes push subscriptions and device tokens, and soft-deletes notifications.
     */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->delete();
        $user->tokens()->delete();
        $user->pushSubscriptions()->delete();
        $user->deviceTokens()->delete();
        $user->reminders()->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }
}
