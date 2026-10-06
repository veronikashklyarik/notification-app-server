<?php

namespace Tests\Feature;

use App\Livewire\NotificationEdit;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_redirects_to_the_back_url(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['schedule_type' => 'every_day', 'times' => ['09:00']]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->set('name', 'Vitamins D3')
            ->call('save')
            ->assertRedirect(route('notifications.show', $notification));

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'name' => 'Vitamins D3']);
    }

    public function test_embedded_save_dispatches_reminder_updated_instead_of_redirecting(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['name' => 'Vitamins', 'schedule_type' => 'every_day', 'times' => ['09:00']]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification, 'embedded' => true])
            ->set('name', 'Vitamins D3')
            ->call('save')
            ->assertDispatched('reminder-updated');

        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'name' => 'Vitamins D3']);
    }

    public function test_close_embedded_dispatches_sheet_closed(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['schedule_type' => 'every_day', 'times' => ['09:00']]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification, 'embedded' => true])
            ->call('closeEmbedded')
            ->assertDispatched('sheet-closed');
    }

    public function test_invalid_save_dispatches_scroll_to_error(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['schedule_type' => 'week_days', 'week_days' => []]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->call('save')
            ->assertDispatched('scroll-to-error')
            ->assertHasErrors('week_days');
    }
}
