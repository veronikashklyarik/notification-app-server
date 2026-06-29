<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    /**
     * Send a web push notification to a subscription.
     *
     * Returns the HTTP status code from the push server, or null on local error.
     *
     * @param  array{icon?: string, url?: string, badge?: string}  $options
     */
    public function send(PushSubscription $subscription, string $title, string $body, array $options = []): ?int
    {
        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('services.vapid.subject'),
                    'publicKey' => config('services.vapid.public_key'),
                    'privateKey' => config('services.vapid.private_key'),
                ],
            ]);

            $sub = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->p256dh,
                    'auth' => $subscription->auth,
                ],
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'data' => $options,
            ]);

            $report = $webPush->sendOneNotification($sub, $payload);

            return $report->getResponse()?->getStatusCode();
        } catch (\Throwable $e) {
            Log::error('WebPush send failed', [
                'subscription_id' => $subscription->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
