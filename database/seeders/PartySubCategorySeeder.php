<?php

namespace Database\Seeders;

use App\Models\PartyCategory;
use App\Models\PartySubCategory;
use Illuminate\Database\Seeder;

class PartySubCategorySeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private array $data = [
        'Property Related' => [
            'Seller (jis se land liya)',
            'Buyer (jo plot khareedta hai)',
            'Dealer / Agent (property dealer commission wala)',
            'Investor / Partner (jo paisa invest kare project mein)',
        ],
        'Construction & Material' => [
            'Supplier – Cement',
            'Supplier – Steel (Saria)',
            'Supplier – Bricks',
            'Supplier – Sand / Bajri',
            'Supplier – Tiles / Marble',
            'Supplier – Paint / Hardware',
            'Supplier – Wood / Carpenter Material',
        ],
        'Labor & Services' => [
            'Contractor',
            'Sub-Contractor',
            'Labor / Worker',
            'Mason (Raj Mistri)',
            'Electrician / Technician (AC, motor etc)',
            'Plumber',
            'Carpenter',
            'Painter',
        ],
        'Office & Operations' => [
            'Employee (Salary)',
            'Office Expense Vendor (stationery, etc)',
            'Utility Provider (Electricity, Gas, Water bills)',
            'Internet / IT Service Provider',
            'Security Service',
        ],
        'Logistics & Transport' => [
            'Transporter',
            'Machinery Rental (excavator, crane etc)',
            'Fuel Supplier (diesel, petrol)',
        ],
        'Government & Legal' => [
            'Government / Authority (fees, taxes)',
            'Legal Advisor / Lawyer',
            'Surveyor (land measurement)',
            'Patwari / Land Record Officer',
        ],
        'Financial' => [
            'Bank',
            'Loan Provider',
            'Investor / Financer',
        ],
        'Misc' => [
            'Misc Vendor',
            'Maintenance Vendor (AC repair, building repair etc)',
            'Cleaning Service',
        ],
    ];

    public function run(): void
    {
        foreach ($this->data as $categoryName => $subNames) {
            $category = PartyCategory::query()->where('name', $categoryName)->first();

            if (! $category) {
                $this->command->warn("Party category not found: {$categoryName}. Skipping its sub categories.");

                continue;
            }

            foreach ($subNames as $name) {
                PartySubCategory::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                    ],
                    []
                );
            }
        }
    }
}
