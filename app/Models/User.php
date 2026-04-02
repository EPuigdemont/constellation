<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'theme',
        'language',
        'avatar_path',
        'avatar_disk',
        'desktop_zoom',
        'vision_board_zoom',
        'diary_display_mode',
        'first_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'first_login_at' => 'datetime',
            'password' => 'hashed',
            'desktop_zoom' => 'float',
            'vision_board_zoom' => 'float',
        ];
    }

    /**
     * Get the user's initials
     */
    /**
     * Get the URL to the user's avatar.
     */
    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return route('avatar.serve', $this);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /** @return HasMany<DiaryEntry, $this> */
    public function diaryEntries(): HasMany
    {
        return $this->hasMany(DiaryEntry::class);
    }

    /** @return HasMany<Note, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /** @return HasMany<Postit, $this> */
    public function postits(): HasMany
    {
        return $this->hasMany(Postit::class);
    }

    /** @return HasMany<Image, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /** @return HasMany<Tag, $this> */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /** @return HasMany<ImportantDate, $this> */
    public function importantDates(): HasMany
    {
        return $this->hasMany(ImportantDate::class);
    }

    /** @return HasMany<Reminder, $this> */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /** @return HasMany<EntityPosition, $this> */
    public function entityPositions(): HasMany
    {
        return $this->hasMany(EntityPosition::class);
    }

    /**
     * Get all friendships initiated by this user (pending or accepted).
     */
    /** @return HasMany<Friendship, $this> */
    public function friendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    /**
     * Get all friendships where this user is the friend (incoming requests).
     */
    /** @return HasMany<Friendship, $this> */
    public function friendRequestsReceived(): HasMany
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Get all accepted friends (users they initiated friendship with).
     */
    /** @return BelongsToMany<User, $this> */
    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'friendships',
            'user_id',
            'friend_id'
        )
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    /**
     * Get all users who have accepted friendship with this user (reverse direction).
     */
    /** @return BelongsToMany<User, $this> */
    public function acceptedByFriends(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'friendships',
            'friend_id',
            'user_id'
        )
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    /**
     * Get all friends (both directions).
     */
    /** @return BelongsToMany<User, $this> */
    public function allFriends(): BelongsToMany
    {
        return $this->friends()->union(
            $this->acceptedByFriends()->getQuery()
        );
    }
}
