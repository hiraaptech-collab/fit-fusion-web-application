<?php

namespace Database\Seeders;

use App\Models\GymSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // One settings row for this installation, without gym tenancy.
        GymSetting::firstOrCreate(['id' => 1], ['name' => 'FitFusion']);
        $this->call(FitnessDataSeeder::class);
    }
}
