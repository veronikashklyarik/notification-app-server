<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteWebPushSubscriptionRequest;
use App\Http\Requests\Api\V1\StoreWebPushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Check whether a given browser-level subscription endpoint actually belongs to
     * the authenticated user. Web Push subscriptions live at the browser/device level,
     * not the app-session level, so a different user who previously subscribed on this
     * same browser would otherwise be mistaken for already being subscribed themselves.
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|string']);

        $subscribed = PushSubscription::query()
            ->where('endpoint', $request->string('endpoint'))
            ->where('user_id', $request->user()->id)
            ->exists();

        return response()->json(['subscribed' => $subscribed]);
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
