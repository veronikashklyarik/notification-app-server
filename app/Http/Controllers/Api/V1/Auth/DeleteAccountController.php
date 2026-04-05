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
     * Revokes all tokens, removes device tokens, soft-deletes notifications, and deletes the user.
     */
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->tokens()->delete();
        $user->deviceTokens()->delete();
        $user->reminders()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }
}
