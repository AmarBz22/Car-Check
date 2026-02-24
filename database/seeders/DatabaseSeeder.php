<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create or update admin user
        // Pass plain password - the model mutator will handle bcryption
        User::updateOrCreate(
            ['email' => 'amar@gmail.com'],
            [
                'name' => 'Amar',
                'password' => 'password', // Plain password - mutator will bcrypt
                'role' => 'admin',
                'status' => 'active',
            ]
        );
    }
}

