<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedType(TransactionType::Income, [
            ['name' => 'Salary', 'icon' => 'work', 'color' => '#2E7D32'],
            ['name' => 'Freelance', 'icon' => 'laptop', 'color' => '#0277BD'],
            ['name' => 'Business', 'icon' => 'storefront', 'color' => '#6A1B9A'],
            ['name' => 'Investments', 'icon' => 'trending_up', 'color' => '#00838F'],
            ['name' => 'Gifts', 'icon' => 'card_giftcard', 'color' => '#AD1457'],
            ['name' => 'Other Income', 'icon' => 'more_horiz', 'color' => '#546E7A'],
        ]);

        $this->seedType(TransactionType::Expense, [
            ['name' => 'Food & Dining', 'icon' => 'restaurant', 'color' => '#EF6C00', 'children' => [
                ['name' => 'Restaurants', 'icon' => 'restaurant_menu', 'color' => '#EF6C00'],
                ['name' => 'Groceries', 'icon' => 'local_grocery_store', 'color' => '#EF6C00'],
                ['name' => 'Coffee Shops', 'icon' => 'local_cafe', 'color' => '#EF6C00'],
            ]],
            ['name' => 'Transportation', 'icon' => 'directions_car', 'color' => '#1565C0', 'children' => [
                ['name' => 'Fuel', 'icon' => 'local_gas_station', 'color' => '#1565C0'],
                ['name' => 'Public Transit', 'icon' => 'directions_bus', 'color' => '#1565C0'],
                ['name' => 'Taxi & Rideshare', 'icon' => 'local_taxi', 'color' => '#1565C0'],
            ]],
            ['name' => 'Shopping', 'icon' => 'shopping_bag', 'color' => '#C2185B'],
            ['name' => 'Bills & Utilities', 'icon' => 'receipt_long', 'color' => '#5D4037'],
            ['name' => 'Entertainment', 'icon' => 'movie', 'color' => '#7B1FA2'],
            ['name' => 'Health & Fitness', 'icon' => 'favorite', 'color' => '#D32F2F'],
            ['name' => 'Education', 'icon' => 'school', 'color' => '#303F9F'],
            ['name' => 'Housing', 'icon' => 'home', 'color' => '#00695C'],
            ['name' => 'Travel', 'icon' => 'flight', 'color' => '#0097A7'],
            ['name' => 'Other Expense', 'icon' => 'more_horiz', 'color' => '#546E7A'],
        ]);
    }

    private function seedType(TransactionType $type, array $categories): void
    {
        foreach ($categories as $data) {
            $children = $data['children'] ?? [];
            unset($data['children']);

            $parent = Category::updateOrCreate(
                ['name' => $data['name'], 'type' => $type->value],
                $data
            );

            foreach ($children as $child) {
                Category::updateOrCreate(
                    ['name' => $child['name'], 'type' => $type->value, 'parent_id' => $parent->id],
                    [...$child, 'type' => $type->value, 'parent_id' => $parent->id]
                );
            }
        }
    }
}
