<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'telegram_link_token_expires_at' => 'datetime',
            'telegram_linked_at' => 'datetime',
            'banned_at' => 'datetime',
        ];
    }

    public function hasLinkedTelegram(): bool
    {
        return filled($this->telegram_chat_id);
    }

    /**
     * Destination for the Telegram notification channel.
     *
     * Named routeNotificationForTelegram so Notification::extend('telegram')
     * and Laravel's routeNotificationFor('telegram') agree on the value.
     */
    public function routeNotificationForTelegram(): ?string
    {
        return $this->telegram_chat_id;
    }

    public function hasPendingTelegramLink(): bool
    {
        return filled($this->telegram_link_token)
            && $this->telegram_link_token_expires_at?->isFuture() === true;
    }

    /**
     * Admins who should be told about a moderation event.
     *
     * @return Collection<int, static>
     */
    public static function admins(): Collection
    {
        /** @var Collection<int, static> */
        return static::query()->where('role', Role::Admin)->get();
    }

    /** @return HasMany<Item, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /** @return HasMany<Claim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function isGuard(): bool
    {
        return $this->role === Role::Guard;
    }

    /**
     * Admins and guards are the only roles allowed to verify a pickup code.
     */
    public function canVerifyPickup(): bool
    {
        return in_array($this->role, [Role::Admin, Role::Guard], true);
    }
}
