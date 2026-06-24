<?php

namespace App\Livewire;

use App\Enums\EventStatus;
use App\Models\NotificationEvent;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Protect;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Protect]
class EventShow extends Component
{
    use AuthorizesRequests;

    public NotificationEvent $event;

    public string $status = '';

    public ?string $comment = '';

    public ?string $postponed_until = null;

    public string $backUrl = '';

    public function mount(NotificationEvent $event): void
    {
        $this->authorize('view', $event);
        $event->load('notification');
        $this->event = $event;
        $this->backUrl = $this->resolveBackUrl(request()->query('back', ''));
    }

    private function resolveBackUrl(string $url): string
    {
        if ($url && str_starts_with($url, url('/'))) {
            return $url;
        }

        return route('events.index');
    }

    public function update(): void
    {
        $this->authorize('update', $this->event);

        $validated = $this->validate([
            'status' => ['required', Rule::in([EventStatus::Done->value, EventStatus::Cancelled->value, EventStatus::Postponed->value])],
            'comment' => ['nullable', 'string', 'max:1000'],
            'postponed_until' => ['nullable', 'date', 'after:now'],
        ]);

        if ($this->status === EventStatus::Postponed->value && ! $this->postponed_until) {
            $this->addError('postponed_until', 'The postpone date is required when postponing.');

            return;
        }

        $data = $validated;

        if ($data['status'] === EventStatus::Postponed->value && isset($data['postponed_until'])) {
            $history = $this->event->postpone_history ?? [];
            $history[] = [
                'from' => $this->event->scheduled_at->toIso8601String(),
                'to' => $data['postponed_until'],
                'at' => now()->toIso8601String(),
            ];
            $data['postpone_history'] = $history;
        }

        if (in_array($data['status'], [EventStatus::Done->value, EventStatus::Cancelled->value, EventStatus::Postponed->value])) {
            $data['completed_at'] = now();
        }

        $this->event->update($data);

        $this->redirect($this->backUrl);
    }

    public function markDone(): void
    {
        $this->authorize('update', $this->event);

        $this->event->update([
            'status' => EventStatus::Done,
            'completed_at' => now(),
        ]);

        $this->redirect($this->backUrl);
    }

    public function markCancelled(): void
    {
        $this->authorize('update', $this->event);

        $this->event->update([
            'status' => EventStatus::Cancelled,
            'completed_at' => now(),
        ]);

        $this->redirect($this->backUrl);
    }

    public function revertToPending(): void
    {
        $this->authorize('update', $this->event);

        $this->event->update([
            'status' => EventStatus::Pending,
            'completed_at' => null,
        ]);

        $this->redirect(route('events.show', $this->event).'?back='.urlencode($this->backUrl));
    }

    public function render(): View
    {
        return view('livewire.event-show');
    }
}
