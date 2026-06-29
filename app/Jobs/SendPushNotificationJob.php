<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  array{icon?: string, url?: string, badge?: string}  $options
     */
    public function __construct(
        public readonly PushSubscription $subscription,
        public readonly string $title,
        public readonly string $body,
        public readonly array $options = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebPushService $webPush): void
    {
        // Re-fetch from DB — subscription may have been deleted by a prior retry (410/404 cleanup)
        $subscription = PushSubscription::find($this->subscription->id);

        if ($subscription === null) {
            return;
        }

        $statusCode = $webPush->send($subscription, $this->title, $this->body, $this->options);

        if (in_array($statusCode, [404, 410], true)) {
            // Subscription is permanently gone — clean it up and complete the job successfully
            $subscription->delete();

            return;
        }

        if ($statusCode === null) {
            // Local PHP error before any HTTP — allow retry
            throw new \RuntimeException('WebPush send failed with a local error (no HTTP response).');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            // Push server error — allow retry
            throw new \RuntimeException("WebPush server returned unexpected status: {$statusCode}.");
        }
    }
}
