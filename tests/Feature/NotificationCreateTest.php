<?php

namespace Tests\Feature;

use App\Livewire\NotificationCreate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_redirects_to_the_reminders_list(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Vitamins')
            ->call('save')
            ->assertRedirect(route('notifications.index'));
    }

    public function test_save_flashes_the_created_reminder_for_the_undo_toast(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Vitamins')
            ->call('save');

        $notification = $user->reminders()->sole();

        $this->assertSame(
            ['id' => $notification->id, 'name' => 'Vitamins'],
            session('createdReminder')
        );
    }

    public function test_vitamins_template_prefills_every_day_schedule(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.create', ['template' => 'vitamins']))
            ->assertOk()
            ->assertSee('Vitamins');
    }

    public function test_unknown_template_is_ignored(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.create', ['template' => 'nope']))
            ->assertOk();
    }

    public function test_embedded_save_dispatches_reminder_created_instead_of_redirecting(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class, ['embedded' => true])
            ->set('name', 'Vitamins')
            ->call('save')
            ->assertDispatched('reminder-created');

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'name' => 'Vitamins']);
        $this->assertNull(session('createdReminder'));
    }

    public function test_close_embedded_dispatches_sheet_closed(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class, ['embedded' => true])
            ->call('closeEmbedded')
            ->assertDispatched('sheet-closed');
    }

    public function test_invalid_save_dispatches_scroll_to_error(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', '')
            ->call('save')
            ->assertDispatched('scroll-to-error')
            ->assertHasErrors('name');
    }

    public function test_duplicate_time_error_names_the_actual_duplicate_time(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('name', 'Vitamins')
            ->set('times', ['08:00', '08:00'])
            ->call('save')
            ->assertHasErrors('times');

        $this->assertSame(
            '08:00 is already in the list — pick another time.',
            $component->errors()->first('times')
        );
    }

    public function test_specific_dates_schedule_description_lists_each_date_and_time(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCreate::class)
            ->set('schedule_type', 'specific_dates')
            ->set('specific_dates', [
                ['date' => '2026-10-13', 'times' => ['09:00']],
                ['date' => '2026-11-10', 'times' => ['08:00', '09:00']],
            ]);

        $this->assertSame(
            'On Tue 13 Oct at 09:00 and Tue 10 Nov at 08:00 and 09:00. After that it ends.',
            $component->get('scheduleDescription')
        );
    }
}
