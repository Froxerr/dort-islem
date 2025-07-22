<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $userRole = Role::where('name', 'User')->first();
        $adminUser = User::create([
            'username'          => 'admin',
            'email'             => 'admin@gmail.com',
            'password'          => Hash::make('123123A'),
            'level'             => 50,
            'xp'                => 500000,
        ]);
        $adminUser->roles()->attach($adminRole);
        User::factory()->count(20)->create()->each(function ($user) use ($userRole) {
            $user->roles()->attach($userRole);
        });
    }
}
