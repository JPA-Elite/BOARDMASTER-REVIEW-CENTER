<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserDeletionService
{
    public function __construct(private readonly FileUploadService $uploads)
    {
    }

    public function delete(User $user): void
    {
        $originalEmail = trim((string) $user->email);
        $avatarPath = $user->avatar_path;
        $userId = (int) $user->id;

        DB::transaction(function () use ($user, $originalEmail, $avatarPath, $userId): void {
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $userId)->delete();
            DB::table('password_reset_tokens')->where('email', $originalEmail)->delete();
            $this->uploads->delete($avatarPath);

            // Anonymize PII before soft delete so personal data is not retained.
            $suffix = $userId.'-'.Str::lower(Str::random(10));
            $user->forceFill([
                'name' => "Deleted User $userId",
                'email' => "deleted+$suffix@deleted.local",
                'google_id' => null,
                'apple_id' => null,
                'password' => Hash::make(Str::random(64)),
                'school' => null,
                'birthdate' => null,
                'gender' => null,
                'place' => null,
                'phone_number' => null,
                'avatar_path' => null,
                'referral_code' => null,
                'referred_by' => null,
                'mobile_demo_seen_at' => null,
                'email_verified_at' => null,
                'remember_token' => null,
            ]);
            $user->save();
            $user->delete();
        });
    }
}
