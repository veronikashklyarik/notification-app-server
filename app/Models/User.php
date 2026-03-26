<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'avatar', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        return null;
    }

    /**
     * Get the notifications for the user.
     *
     * @return HasMany<Notification, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the notification events for the user.
     *
     * @return HasMany<NotificationEvent, $this>
     */
    public function notificationEvents(): HasMany
    {
        return $this->hasMany(NotificationEvent::class);
    }
}
