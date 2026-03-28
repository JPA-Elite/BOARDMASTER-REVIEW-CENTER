<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\MobileResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, MobileResetPasswordNotification::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, MobileResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email));

            $response->assertStatus(200);
            $response->assertSee('Reset Password');

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, MobileResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'StrongPass1!',
                'password_confirmation' => 'StrongPass1!',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('password.reset.success', [
                    'token' => $notification->token,
                    'email' => $user->email,
                ]));

            return true;
        });
    }

    public function test_used_reset_link_shows_expired_state(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, MobileResetPasswordNotification::class, function ($notification) use ($user) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'StrongPass1!',
                'password_confirmation' => 'StrongPass1!',
            ])->assertRedirect(route('password.reset.success', [
                'token' => $notification->token,
                'email' => $user->email,
            ]));

            $response = $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email));
            $response->assertStatus(200);
            $response->assertSee('expired or was already used', false);

            return true;
        });
    }
}

