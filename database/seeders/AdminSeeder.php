<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::create([
            'name' => 'admin2',
            'phone' => '01061164326',
            'password' => '12345678',
            'country' => 'مصر',
            'otp' => '111111',
            'city' => 'سوهاج',
        ]);
    }
}