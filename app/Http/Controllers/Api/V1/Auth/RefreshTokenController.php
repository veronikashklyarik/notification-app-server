<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefreshTokenController extends Controller
{
    /**
     * Refresh Token
     *
     * Exchanges a valid refresh token for a new access/refresh token pair.
     * The old refresh token is revoked after use.
     *
     * This endpoint requires authentication with a **refresh token** (not an access token).
     */
    public function store(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();

        if (! in_array('refresh', $currentToken->abilities)) {
            abort(403, 'This token cannot be used for refresh.');
        }

        $deviceName = str_replace('-refresh', '', $currentToken->name);

        $accessToken = $request->user()->createToken(
            $deviceName,
            ['*'],
            now()->addMinutes(config('auth.api_tokens.access_expiration_minutes')),
        )->plainTextToken;

        $refreshToken = $request->user()->createToken(
            $deviceName.'-refresh',
            ['refresh'],
            now()->addMinutes(config('auth.api_tokens.refresh_expiration_minutes')),
        )->plainTextToken;

        $currentToken->delete();

        return response()->json([
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
        ]);
    }
}
