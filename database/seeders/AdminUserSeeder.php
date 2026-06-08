<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (!$email || !$password) {
            throw new InvalidArgumentException('ADMIN_EMAIL and ADMIN_PASSWORD must be defined in the environment.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'first_name' => env('ADMIN_FIRST_NAME', 'System'),
                'last_name' => env('ADMIN_LAST_NAME', 'Admin'),
                'phone' => env('ADMIN_PHONE'),
                'password' => Hash::make($password),
                'role' => 'admin',
            ]
        );
    }
}
