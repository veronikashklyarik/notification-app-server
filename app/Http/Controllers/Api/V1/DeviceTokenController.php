<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteDeviceTokenRequest;
use App\Http\Requests\Api\V1\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    /**
     * Store Device Token
     *
     * Registers or updates an FCM device token for push notifications.
     * If the token already exists, it will be reassigned to the authenticated user.
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        DeviceToken::query()->updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->platform,
                'device_name' => $request->device_name,
            ],
        );

        return response()->json(['message' => 'Device token registered successfully.'], 201);
    }

    /**
     * Delete Device Token
     *
     * Removes an FCM device token. Should be called before logout or when push notifications are disabled.
     */
    public function destroy(DeleteDeviceTokenRequest $request): JsonResponse
    {
        DeviceToken::query()
            ->where('token', $request->token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Device token removed successfully.']);
    }
}
