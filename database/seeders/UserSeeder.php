<?php

// database/seeders/UserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {

        User::firstOrCreate(
            ['email' => 'blassy@example.com'],
            [
                'username' => 'blas',
                'password' => Hash::make('password123'),
            ]
        );
        User::firstOrCreate(
            ['email' => 'blassy@exple.com'],
            [
            'username' => 'blauuuuus',
            'password' => Hash::make('password123'),
            ]);

        User::firstOrCreate(
            ['email' => 'baba@eapppppppple.com'],
            [
            'username' => 'jahome',
            'password' => Hash::make('password123'),
            ]);

        User::firstOrCreate(
            ['email' => 'mimi@exame.com'],
            [
            'username' => 'mary',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate(    
            ['email' => 'bri@ple.com'],
            [
            'username' => 'Brielle',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate(
            ['email' => 'mau@e.com'],
            [
            'username' => 'Kamau',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate(
            ['email' => 'ochi@ee.com'],
            [
            'username' => 'Ochieng',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate(
            ['email' => 'meme@exuuule.com'],
            [
            'username' => 'Nilmah',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate(
            ['email' => 'juno@examppppple.com'],
            [
            'username' => 'Juno',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate(
            ['email' => 'Jude@exxxxxxample.com'],
            [
            'username' => 'Jude',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate(
            ['email' => 'number@exammmmmple.com'],
            [
            'username' => 'mimimimi',
            'password' => Hash::make('password123'),
        ]);  

        User::firstOrCreate(
            ['email' => 'blaWachassy@exampllllllle.com'],
            [
            'username' => 'wachs',
            'password' => Hash::make('password123'),
        ]);

        User::firstOrCreate(
            ['email' => 'nahiii@exampleeeeeee.com'],
            [
            'username' => 'ingine',
            'password' => Hash::make('password123'),
        ]);

        User::factory()->count(10)->create(); // optional: needs UserFactory
    }
}
