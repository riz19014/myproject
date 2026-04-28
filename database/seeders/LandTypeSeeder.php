<?php

namespace Database\Seeders;

use App\Models\LandType;
use Illuminate\Database\Seeder;

class LandTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Factory', 'House', 'Plot'] as $name) {
            LandType::firstOrCreate(
                ['name' => $name],
                ['party_sub_category_id' => null]
            );
        }
    }
}
