<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\Api\TokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    /**
     * Test that the AuthController login uses refresh token pattern
     */
    public function test_auth_controller_login_uses_refresh_token_pattern(): void
    {
        // Mock the User model
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['email', 'test@example.com'],
        ]);

        // Create a mock for the TokenService
        $tokenService = $this->createMock(TokenService::class);
        $tokenService->expects($this->once())
            ->method('createAdvancedToken')
            ->willReturn([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_at' => now()->addHour()->timestamp,
            ]);

        // Bind the mock TokenService to the service container
        $this->app->instance(TokenService::class, $tokenService);

        // Mock the User static method where->first
        $this->partialMock(User::class, function ($mock) use ($user) {
            $mock->shouldReceive('where->first')->andReturn($user);
        });

        // Mock the Hash facade to return true for the password check
        Hash::shouldReceive('check')
            ->with('password', null)
            ->andReturn(true);

        // Send a login request
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'testing',
        ]);

        // Assert response contains token, expiry, and refresh token cookie
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'expires_at',
            'token_type',
        ]);
        $response->assertJsonPath('access_token', 'test-access-token');
        $response->assertCookie('refresh_token', 'test-refresh-token');
    }

    /**
     * Test that the AuthController refresh token method works
     */
    public function test_auth_controller_refresh_token_method(): void
    {
        // Create a mock for the TokenService
        $tokenService = $this->createMock(TokenService::class);
        $tokenService->expects($this->once())
            ->method('refreshToken')
            ->willReturn([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_at' => now()->addHour()->timestamp,
            ]);

        // Bind the mock TokenService to the service container
        $this->app->instance(TokenService::class, $tokenService);

        // Create a user for auth
        $user = new User([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Mock the RefreshToken model to find token and return userId
        $this->partialMock('App\Models\RefreshToken', function ($mock) {
            $mock->shouldReceive('where->where->first')
                ->andReturn((object) ['user_id' => 1]);
        });

        // Mock the User::find method to return our user
        $this->partialMock(User::class, function ($mock) use ($user) {
            $mock->shouldReceive('find')->with(1)->andReturn($user);
        });

        // Send refresh token request with refresh token in request body
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'old-refresh-token',
        ]);

        // Assert response contains the new token and cookie
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'expires_at',
            'token_type',
        ]);
        $response->assertJsonPath('access_token', 'new-access-token');
        $response->assertCookie('refresh_token', 'new-refresh-token');
    }

    /**
     * Test that the AuthController logout method revokes refresh tokens
     */
    public function test_auth_controller_logout_revokes_refresh_tokens(): void
    {
        // Create a mock user and token for the test
        $user = new User([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $refreshTokens = $this->createMock('Illuminate\Database\Eloquent\Relations\HasMany');
        $refreshTokens->expects($this->once())
            ->method('where')
            ->with('access_token_id', 123)
            ->willReturnSelf();
        $refreshTokens->expects($this->once())
            ->method('delete');

        $user->method('__call')
            ->willReturnMap([
                ['refreshTokens', [], $refreshTokens],
            ]);

        $token = $this->createMock('Laravel\Sanctum\PersonalAccessToken');
        $token->id = 123;
        $token->expects($this->once())
            ->method('delete');

        // Create a mock request with authenticated user
        $request = Request::create('/api/logout', 'POST');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Mock the current access token method
        $user->method('currentAccessToken')
            ->willReturn($token);

        // Create controller and call the logout method
        $controller = new \App\Http\Controllers\Api\AuthController;
        $response = $controller->logout($request);

        // Assert the cookie is expired
        $this->assertEquals('refresh_token', $response->cookie('refresh_token')->getName());
        $this->assertEquals('', $response->cookie('refresh_token')->getValue());
    }
}
