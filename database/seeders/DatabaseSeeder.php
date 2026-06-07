<?php

namespace Database\Seeders;

use App\Models\Service;
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
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'primary_phone' => '1234567890',
            'type' => 'admin'
        ]);

        Service::create([
            'title' => 'غسيل ',
            'slug' => 'Gasel',
            'description' => 'وصف الخدمة 1',
            'status' => true,
        ]);

        Service::create([
            'title' => 'تنظيف',
            'slug' => 'Tanzif',
            'description' => 'وصف الخدمة 2',
            'status' => true,
        ]);
    }
}
