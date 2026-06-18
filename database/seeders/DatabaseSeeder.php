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
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@task.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@task.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Team Lead User',
            'email' => 'teamlead@task.com',
            'password' => bcrypt('password'),
            'role' => 'team_lead',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Employee User 1',
            'email' => 'employee1@task.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Employee User 2',
            'email' => 'employee2@task.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Client User',
            'email' => 'client@task.com',
            'password' => bcrypt('password'),
            'role' => 'client',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'Inactive Employee',
            'email' => 'inactive@task.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'inactive',
        ]);
    }
}
