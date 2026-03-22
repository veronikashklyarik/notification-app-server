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
     * Authenticates the user and returns a Sanctum API token scoped to the given device name.
     *
     * **Token expiry** is determined by the user's "Remember Me" web session:
     * - If the user has an active Remember Me session (`remember_token` is set) — the token expires in **1 year**.
     * - Otherwise — the token expires in **1 day**.
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

        $expiresAt = $user->remember_token ? now()->addYear() : now()->addDay();
        $token = $user->createToken($request->device_name, ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }
}
