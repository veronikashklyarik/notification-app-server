<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * Register
     *
     * Creates a new user account and returns a Sanctum API token pair scoped to the given device name.
     *
     * Returns an **access token** (short-lived, for regular API requests) and a **refresh token**
     * (longer-lived, can only be used to obtain a new token pair via the refresh endpoint).
     *
     * @unauthenticated
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->except('device_name'));

        $accessToken = $user->createToken(
            $request->device_name,
            ['*'],
            now()->addMinutes(config('auth.api_tokens.access_expiration_minutes')),
        )->plainTextToken;

        $refreshToken = $user->createToken(
            $request->device_name.'-refresh',
            ['refresh'],
            now()->addMinutes(config('auth.api_tokens.refresh_expiration_minutes')),
        )->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
        ], 201);
    }
}
