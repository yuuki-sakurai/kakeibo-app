<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_session_logout_and_login(): void
    {
        $this->getJson('/api/v1/auth/session')->assertOk()->assertJsonPath('user', null)->assertJsonStructure(['csrfToken']);
        $this->postJson('/api/v1/auth/register', ['name' => '利用者', 'email' => 'TEST@example.com', 'password' => 'long-password-123', 'password_confirmation' => 'long-password-123'])
            ->assertCreated()->assertJsonPath('user.email', 'test@example.com')->assertJsonMissingPath('user.password');
        $this->assertTrue(Hash::check('long-password-123', User::first()->password));
        $this->getJson('/api/v1/categories')->assertOk();
        $this->postJson('/api/v1/auth/logout')->assertOk()->assertJsonPath('user', null);
        $this->getJson('/api/v1/categories')->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['email' => 'test@example.com', 'password' => 'wrong'])->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => 'TEST@example.com', 'password' => 'long-password-123'])->assertOk()->assertJsonPath('user.email', 'test@example.com');
    }

    public function test_anonymous_requests_cannot_access_any_household_endpoint(): void
    {
        foreach (['categories', 'expenses?date=2026-09-07', 'expenses/1', 'monthly-summary?year=2026&month=9', 'stores'] as $path) {
            $this->getJson('/api/v1/'.$path)->assertUnauthorized();
        }
        foreach (['categories', 'expenses', 'expense-imports', 'expense-imports/preview'] as $path) {
            $this->postJson('/api/v1/'.$path, [])->assertUnauthorized();
        }
        $this->putJson('/api/v1/expenses/1', [])->assertUnauthorized();
    }

    public function test_registration_validates_confirmation_length_and_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        foreach ([['email' => 'taken@example.com'], ['password' => 'short'], ['password' => str_repeat('a', 73), 'password_confirmation' => str_repeat('a', 73)], ['password_confirmation' => 'not-matching']] as $override) {
            $this->postJson('/api/v1/auth/register', array_replace(['name' => '利用者', 'email' => 'new@example.com', 'password' => 'long-password-123', 'password_confirmation' => 'long-password-123'], $override))->assertUnprocessable();
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_failed_logins_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'absent@example.com', 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson('/api/v1/auth/login', ['email' => 'absent@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_cross_origin_mutations_require_matching_csrf_token(): void
    {
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->withSession(['_token' => 'expected-token'])->postJson('/api/v1/auth/logout')->assertStatus(419);
        $this->withSession(['_token' => 'expected-token'])->withHeader('X-CSRF-TOKEN', 'expected-token')->postJson('/api/v1/auth/logout')->assertOk();
    }
}
