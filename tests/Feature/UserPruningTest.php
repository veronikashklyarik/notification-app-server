<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deleted_over_30_days_ago_is_erased_with_its_data(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.webp', 'image');

        $user = User::factory()->create(['avatar' => 'avatars/old.webp']);
        $reminder = Notification::factory()->create(['user_id' => $user->id]);
        $event = NotificationEvent::factory()->create(['user_id' => $user->id, 'notification_id' => $reminder->id]);
        $subscription = PushSubscription::factory()->create(['user_id' => $user->id]);
        $user->createToken('phone');
        $user->delete();
        $this->travel(31)->days();

        $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $reminder->id]);
        $this->assertDatabaseMissing('notification_events', ['id' => $event->id]);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        Storage::disk('public')->assertMissing('avatars/old.webp');
    }

    public function test_recently_deleted_and_active_accounts_are_kept(): void
    {
        $recentlyDeleted = User::factory()->create();
        $recentlyDeleted->delete();
        $active = User::factory()->create();
        $this->travel(29)->days();

        $this->artisan('model:prune', ['--model' => [User::class]])->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $recentlyDeleted->id]);
        $this->assertNotSoftDeleted('users', ['id' => $active->id]);
    }
}
