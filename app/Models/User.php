<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FriendshipStatus;
use App\Enums\Theme;
use App\Enums\Tier;
use App\Services\ThemeResolverService;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string|null $avatar_path
 * @property string|null $avatar_disk
 * @property string $theme
 * @property bool $automatic_themes
 * @property string $language
 * @property float $desktop_zoom
 * @property float $vision_board_zoom
 * @property string $diary_display_mode
 * @property CarbonInterface|null $first_login_at
 * @property Tier $tier
 * @property CarbonInterface|null $guest_expires_at
 * @property CarbonInterface|null $guest_created_at
 */
class User extends Authenticatable // implements MustVerifyEmail
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
        'automatic_themes',
        'language',
        'avatar_path',
        'avatar_disk',
        'desktop_zoom',
        'vision_board_zoom',
        'diary_display_mode',
        'tier',
        'first_login_at',
        'guest_expires_at',
        'guest_created_at',
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
            'guest_expires_at' => 'datetime',
            'guest_created_at' => 'datetime',
            'password' => 'hashed',
            'desktop_zoom' => 'float',
            'vision_board_zoom' => 'float',
            'automatic_themes' => 'boolean',
            'tier' => Tier::class,
        ];
    }

    public function resolvedTheme(?CarbonInterface $now = null): Theme
    {
        return app(ThemeResolverService::class)->resolveForUser($this, $now);
    }

    public function activeTheme(?CarbonInterface $now = null): string
    {
        return $this->resolvedTheme($now)->value;
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

    /**
     * Check if this is a guest user.
     */
    public function isGuest(): bool
    {
        return $this->tier === Tier::Guest;
    }

    /**
     * Check if guest account has expired.
     */
    public function isGuestExpired(): bool
    {
        if (! $this->isGuest() || ! $this->guest_expires_at) {
            return false;
        }

        return now()->greaterThanOrEqualTo($this->guest_expires_at);
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
            ->wherePivot('status', FriendshipStatus::Accepted->value)
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
            ->wherePivot('status', FriendshipStatus::Accepted->value)
            ->withTimestamps();
    }

    /**
     * Get all friends (both directions).
     */
    /** @return EloquentCollection<int, User> */
    public function allFriends(): EloquentCollection
    {
        return $this->friends()
            ->get()
            ->merge($this->acceptedByFriends()->get())
            ->unique('id')
            ->values();
    }
}
