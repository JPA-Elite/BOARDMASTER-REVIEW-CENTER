<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Contracts\ReferralRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly ReferralRepositoryInterface $referrals,
        private readonly FileUploadService $uploads,
    ) {
    }

    public function paginate(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        $users = $this->users->paginateWithFilters($search, $perPage);

        $users->through(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar_url' => $user->avatar_url,
            'referral_code' => $user->referral_code,
            'referred_by' => $user->referrer?->name,
            'created_at' => $user->created_at?->format('M d, Y'),
        ]);

        return $users;
    }

    public function listReferrers(?int $excludeId = null): Collection
    {
        return $this->users->listReferrers($excludeId);
    }

    public function create(array $data): User
    {
        $user = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'avatar_path' => $data['avatar_path'] ?? null,
            'referral_code' => $data['referral_code'] ?? null,
            'referred_by' => $data['referred_by'] ?? null,
        ]);

        $this->referrals->syncForInvited($user->id, $data['referred_by'] ?? null);

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'referral_code' => $data['referral_code'] ?? null,
            'referred_by' => $data['referred_by'] ?? null,
        ];

        if (! empty($data['avatar_path'])) {
            $payload['avatar_path'] = $data['avatar_path'];
        }

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user = $this->users->update($user, $payload);
        $this->referrals->syncForInvited($user->id, $data['referred_by'] ?? null);

        return $user;
    }

    public function delete(User $user, int $currentUserId): bool
    {
        if ($user->id === $currentUserId) {
            return false;
        }

        $this->uploads->delete($user->avatar_path);
        $this->users->delete($user);

        return true;
    }
}
