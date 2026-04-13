<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_can_delete_account_with_correct_password(): void
    {
        $user = User::factory()->create();
        $originalEmail = $user->email;
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/mobile/account', [
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Your account has been deleted successfully.',
            ]);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'email' => $originalEmail,
        ]);
    }

    public function test_password_is_required_for_non_google_mobile_account_deletion(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/mobile/account');

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_google_origin_mobile_user_can_delete_account_without_password(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-test-123',
        ]);
        $originalEmail = $user->email;
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/mobile/account');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Your account has been deleted successfully.',
            ]);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'email' => $originalEmail,
        ]);
    }
}
