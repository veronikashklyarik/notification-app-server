<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteWebPushSubscriptionRequest;
use App\Http\Requests\Api\V1\StoreWebPushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    /**
     * Register or update a web push subscription for the authenticated user.
     *
     * If the same endpoint is re-registered by the same user (e.g. refreshed keys),
     * the existing row is updated. Multiple users on the same device each own their
     * own row, scoped by (endpoint, user_id).
     */
    public function store(StoreWebPushSubscriptionRequest $request): JsonResponse
    {
        PushSubscription::query()->updateOrCreate(
            [
                'endpoint' => $request->input('endpoint'),
                'user_id' => $request->user()->id,
            ],
            [
                'p256dh' => $request->input('keys.p256dh'),
                'auth' => $request->input('keys.auth'),
            ],
        );

        return response()->json(['message' => 'Push subscription registered successfully.'], 201);
    }

    /**
     * Remove a web push subscription.
     */
    public function destroy(DeleteWebPushSubscriptionRequest $request): JsonResponse
    {
        PushSubscription::query()
            ->where('endpoint', $request->input('endpoint'))
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Push subscription removed successfully.']);
    }

    /**
     * Return the VAPID public key for the PWA to use when subscribing.
     */
    public function vapidPublicKey(): JsonResponse
    {
        if (! config('services.vapid.public_key')) {
            return response()->json(['message' => 'VAPID public key is not configured.'], 503);
        }

        return response()->json(['public_key' => config('services.vapid.public_key')]);
    }
}
