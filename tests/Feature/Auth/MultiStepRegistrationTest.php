<?php

use App\Enums\IdTypeEnum;
use App\Enums\PartialRegistrationStatusEnum;
use App\Models\Family;
use App\Models\PartialRegistration;
use App\Models\User;

test('user can complete registration step 1', function () {
    $payload = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'id_type' => IdTypeEnum::PASSPORT->value,
        'id_number' => '123456789',
    ];

    $response = $this->postJson('/api/v1/register/step-1', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'registration_id'
        ]);

    $this->assertDatabaseHas('partial_registrations', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'id_type' => IdTypeEnum::PASSPORT->value,
        'id_number' => '123456789',
    ]);

    $this->assertDatabaseHas('families', [
        'name' => "Doe's Family",
    ]);
});

test('user can complete registration step 2', function () {
    $family = Family::factory()->create();
    $partialRegistration = PartialRegistration::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'id_type' => IdTypeEnum::PASSPORT,
        'id_number' => '123456789',
        'family_id' => $family->id,
    ]);

    $payload = [
        'registration_id' => $partialRegistration->id,
        'email' => 'john.doe@example.com',
        'phone_number' => '+1234567890',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ];

    $response = $this->postJson('/api/v1/register/step-2', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'registration_id'
        ]);

    $this->assertDatabaseHas('partial_registrations', [
        'id' => $partialRegistration->id,
        'email' => 'john.doe@example.com',
        'phone_number' => '+1234567890',
        'status' => PartialRegistrationStatusEnum::Completed->value,
    ]);
});

test('user can complete final registration step', function () {
    $family = Family::factory()->create();
    $partialRegistration = PartialRegistration::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'id_type' => IdTypeEnum::PASSPORT,
        'id_number' => '123456789',
        'email' => 'john.doe@example.com',
        'phone_number' => '+1234567890',
        'password' => Hash::make('Password123!'),
        'status' => PartialRegistrationStatusEnum::Completed,
        'family_id' => $family->id,
    ]);

    $response = $this->postJson('/api/v1/register', [
        'registration_id' => $partialRegistration->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'email',
                'created_at',
                'updated_at',
                'profile' => [
                    'first_name',
                    'last_name',
                    'phone_number',
                    'id_type',
                    'id_number',
                ],
                'family' => [
                    'id',
                    'name',
                ]
            ]
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john.doe@example.com',
    ]);

    $this->assertDatabaseMissing('partial_registrations', [
        'id' => $partialRegistration->id,
    ]);
}); 