<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateAvatarRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Show Profile
     *
     * Return the authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Update Profile
     *
     * Update the authenticated user's profile. All fields are optional — only provided fields will be updated.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return response()->json([
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Update Avatar
     *
     * Replace the authenticated user's profile photo.
     */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            Log::info('[DEBUG] Upload Info:', [
                'is_valid' => $file->isValid(),
                'error_code' => $file->getError(), // Узнаем причину (1, 3, 4 или 6)
                'error_msg' => $file->getErrorMessage(),
                'mime_from_client' => $file->getClientMimeType(),
                'client_extension' => $file->getClientOriginalExtension(),
            ]);
        } else {
            Log::warning('[DEBUG] File "avatar" not found in request');
        }

        try {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');

            if (! $path) {
                return response()->json(['message' => 'Failed to store the avatar. Please try again.'], 500);
            }

            $user->update(['avatar' => $path]);
        } catch (Throwable $e) {
            Log::error('Avatar upload failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to store the avatar. Please try again.'], 500);
        }

        return response()->json([
            'user' => new UserResource($user->fresh()),
        ]);
    }
}
