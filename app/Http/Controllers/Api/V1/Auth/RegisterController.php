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
     * Creates a new user account and returns a Sanctum API token scoped to the given device name.
     *
     * New accounts have no active Remember Me session, so the token always expires in **1 day**.
     * Log in via the login endpoint after establishing a web session with Remember Me to receive a longer-lived token.
     *
     * @unauthenticated
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->except('device_name'));

        $expiresAt = $user->remember_token ? now()->addYear() : now()->addDay();
        $token = $user->createToken($request->device_name, ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}
