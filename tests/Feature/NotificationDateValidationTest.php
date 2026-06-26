<?php

namespace Tests\Feature;

use App\Livewire\NotificationCreate;
use App\Livewire\NotificationEdit;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationDateValidationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // NotificationCreate
    // -------------------------------------------------------------------------

    public function test_create_allows_ends_at_equal_to_starts_at(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'One-time event')
            ->set('schedule_type', 'every_day')
            ->set('starts_at', '2039-01-26')
            ->set('ends_at', '2039-01-26')
            ->call('save')
            ->assertHasNoErrors('ends_at');
    }

    public function test_create_rejects_ends_at_before_starts_at(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Bad dates')
            ->set('schedule_type', 'every_day')
            ->set('starts_at', '2039-01-26')
            ->set('ends_at', '2039-01-25')
            ->call('save')
            ->assertHasErrors('ends_at');
    }

    public function test_create_saves_null_when_ends_at_is_empty_string(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'No end date')
            ->set('schedule_type', 'every_day')
            ->set('starts_at', '2039-01-26')
            ->set('ends_at', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'name' => 'No end date',
            'ends_at' => null,
        ]);
    }

    public function test_create_saves_null_when_starts_at_is_empty_string(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'No start date')
            ->set('schedule_type', 'every_day')
            ->set('starts_at', '')
            ->set('ends_at', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'name' => 'No start date',
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // NotificationEdit
    // -------------------------------------------------------------------------

    public function test_edit_allows_ends_at_equal_to_starts_at(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->daily()->for($user)->create([
            'starts_at' => '2039-01-26',
            'ends_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->set('ends_at', '2039-01-26')
            ->call('save')
            ->assertHasNoErrors('ends_at');
    }

    public function test_edit_rejects_ends_at_before_starts_at(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->daily()->for($user)->create([
            'starts_at' => '2039-01-26',
            'ends_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->set('ends_at', '2039-01-25')
            ->call('save')
            ->assertHasErrors('ends_at');
    }

    public function test_edit_saves_null_when_ends_at_is_reset_to_empty_string(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->daily()->for($user)->create([
            'starts_at' => '2039-01-26',
            'ends_at' => '2039-06-01',
        ]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->set('ends_at', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'ends_at' => null,
        ]);
    }

    public function test_edit_saves_null_when_starts_at_is_reset_to_empty_string(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->daily()->for($user)->create([
            'starts_at' => '2039-01-26',
            'ends_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(NotificationEdit::class, ['notification' => $notification])
            ->set('starts_at', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'starts_at' => null,
        ]);
    }
}
