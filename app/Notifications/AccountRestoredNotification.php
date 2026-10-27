<?php

namespace App\Notifications;

use App\Enums\RestoreMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class AccountRestoredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RestoreMethod $method, public Carbon $restoredAt) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $restoredAt = $this->restoredAt->copy()
            ->setTimezone($notifiable->timezone ?? 'UTC')
            ->locale($notifiable->preferredLocale())
            ->isoFormat('LLL');

        return (new MailMessage)
            ->subject(__('Your Notifyr account was restored'))
            ->line(__('Your deleted Notifyr account was restored on :date by :method. Your reminders are back.', [
                'date' => $restoredAt,
                'method' => $this->method->label(),
            ]))
            ->line(__('Turn notifications back on in Settings on each device where you want them.'))
            ->action(__('Open Notifyr'), route('home'))
            ->line(__('If this wasn\'t you, reset your password right away and contact us at :email.', [
                'email' => config('mail.from.address'),
            ]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
