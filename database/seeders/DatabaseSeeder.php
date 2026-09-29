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
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        if (app()->environment(['local', 'testing'])) {
            User::firstOrCreate(
                ['email' => 'admin@sekolah.test'],
                [
                    'name' => 'Administrator Sekolah',
                    'password' => env('SCHOOL_ADMIN_PASSWORD', 'password'),
                    'role' => 'admin',
                ],
            );
        }
    }
}
