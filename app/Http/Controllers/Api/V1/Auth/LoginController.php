<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Login
     *
     * Authenticates the user and returns a Sanctum API token pair scoped to the given device name.
     *
     * Returns an **access token** (short-lived, for regular API requests) and a **refresh token**
     * (longer-lived, can only be used to obtain a new token pair via the refresh endpoint).
     *
     * @unauthenticated
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

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
        ]);
    }
}
