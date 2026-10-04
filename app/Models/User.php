<?php

namespace App\Models;

use App\Concerns\HasProfilePhoto;
use App\Concerns\SearchesText;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property bool $email_notifications
 * @property string|null $profile_photo_path
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['profile_photo_path', 'password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /**
     * @var list<string>
     */
    protected $appends = ['avatar'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasProfilePhoto, Notifiable, PasskeyAuthenticatable, SearchesText, TwoFactorAuthenticatable;

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
            'email_notifications' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user has verified their email address. Always true when
     * email verification is disabled.
     */
    public function hasVerifiedEmail(): bool
    {
        return ! config('auth.verify_email') || ! is_null($this->email_verified_at);
    }

    /**
     * Send the email verification notification, unless verification is disabled.
     */
    public function sendEmailVerificationNotification(): void
    {
        if (config('auth.verify_email')) {
            $this->notify(new VerifyEmail);
        }
    }

    /**
     * Issues the user is watching.
     *
     * @return BelongsToMany<Issue, $this>
     */
    public function watchedIssues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_watcher')->withPivot('created_at');
    }

    /**
     * @return BelongsToMany<Board, $this>
     */
    public function boards(): BelongsToMany
    {
        return $this->belongsToMany(Board::class)->withPivot('role')->withTimestamps();
    }

    /**
     * Users whose name or email contains the search text. An empty search matches everyone.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function matching(Builder $query, string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $users) use ($search): void {
            self::whereColumnContains($users, 'name', $search);
            self::whereColumnContains($users, 'email', $search, 'or');
        });
    }
}
