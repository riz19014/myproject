<?php

namespace Database\Seeders;

use App\Models\PartyCategory;
use Illuminate\Database\Seeder;

class PartyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Property Related',
            'Construction & Material',
            'Labor & Services',
            'Office & Operations',
            'Logistics & Transport',
            'Government & Legal',
            'Financial',
            'Misc',
        ];

        foreach ($names as $name) {
            PartyCategory::firstOrCreate(
                ['name' => $name],
                []
            );
        }
    }
}
