<?php

namespace App\Console\Commands;

use App\Enums\IdTypeEnum;
use Filament\Commands\MakeUserCommand;
use Illuminate\Support\Facades\Hash;

class MakeFilamentUserOverride extends MakeUserCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:filament-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Filament user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->ask('Email Address');
        $password = $this->secret('Password');

        $this->getUserModel()::create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('Filament user created successfully!');

        return static::SUCCESS;
    }
}
