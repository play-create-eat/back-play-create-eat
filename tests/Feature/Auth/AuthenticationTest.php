<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
});

test('users can login with correct credentials', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'email',
                'created_at',
                'updated_at',
            ]
        ]);
});

test('users cannot login with incorrect password', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

test('users can logout', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/logout');

    $response->assertStatus(204);
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('users can request password reset', function () {
    $response = $this->postJson('/api/v1/forgot-password', [
        'phone_number' => $this->user->profile->phone_number,
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'OTP send successfully.']);

    $this->assertDatabaseHas('otp_codes', [
        'user_id' => $this->user->id,
        'purpose' => 'forgot_password',
    ]);
});

test('users can reset password with valid otp', function () {
    // First request the password reset
    $this->postJson('/api/v1/forgot-password', [
        'phone_number' => $this->user->profile->phone_number,
    ]);

    $otpCode = $this->user->otpCodes()->latest()->first();

    $response = $this->postJson('/api/v1/reset-password', [
        'otp' => $otpCode->code,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Password reset successfully.']);

    // Verify we can login with new password
    $this->postJson('/api/v1/login', [
        'email' => $this->user->email,
        'password' => 'newpassword123',
    ])->assertStatus(200);
});
