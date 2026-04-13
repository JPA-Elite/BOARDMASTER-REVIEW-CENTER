<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\MobileResetPasswordNotification;
use App\Notifications\MobileVerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Throwable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'google_id',
        'school',
        'birthdate',
        'gender',
        'place',
        'phone_number',
        'password',
        'avatar_path',
        'role',
        'referral_code',
        'referred_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
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
            'password' => 'hashed',
            'birthdate' => 'date',
            'mobile_demo_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! $user->role) {
                $user->role = 'user';
            }

            if (! $user->referral_code) {
                $user->referral_code = static::generateReferralCode();
            }
        });
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::query()->where('referral_code', $code)->exists());

        return $code;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        if (filter_var($this->avatar_path, FILTER_VALIDATE_URL)) {
            return $this->avatar_path;
        }

        [$storedDisk, $storedPath] = $this->splitAvatarPath($this->avatar_path);
        if ($storedPath === '') {
            return null;
        }

        $candidateDisks = [];
        if ($storedDisk) {
            $candidateDisks[] = $storedDisk;
        }
        $candidateDisks[] = (string) config('uploads.local_disk', 'public');
        $candidateDisks[] = (string) config('uploads.remote_disk', 's3');
        $candidateDisks = array_values(array_unique(array_filter($candidateDisks)));

        foreach ($candidateDisks as $disk) {
            if (! config("filesystems.disks.$disk")) {
                continue;
            }

            try {
                if ($storedDisk === $disk || Storage::disk($disk)->exists($storedPath)) {
                    return Storage::disk($disk)->url($storedPath);
                }
            } catch (Throwable) {
                // Ignore temporary disk connectivity issues and continue fallback chain.
            }
        }

        if (App::environment('local')) {
            return '/storage/'.$storedPath;
        }

        return null;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new MobileVerifyEmailNotification());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new MobileResetPasswordNotification($token));
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function sentReferrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'inviter_id');
    }

    public function receivedReferral(): HasOne
    {
        return $this->hasOne(Referral::class, 'invited_user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function createdQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * @return array{0:string|null,1:string}
     */
    private function splitAvatarPath(string $value): array
    {
        if (! str_contains($value, ':')) {
            return [null, ltrim($value, '/')];
        }

        [$disk, $path] = explode(':', $value, 2);
        $disk = trim($disk);
        $path = ltrim(trim($path), '/');

        if ($disk === '' || $path === '') {
            return [null, ltrim($value, '/')];
        }

        return [$disk, $path];
    }
}
