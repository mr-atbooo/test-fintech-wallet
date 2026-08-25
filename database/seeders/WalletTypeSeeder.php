<?php

namespace Database\Seeders;

use App\Enums\WalletTypeCode;
use App\Models\WalletType;
use Illuminate\Database\Seeder;

class WalletTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Cash', 'code' => WalletTypeCode::Cash->value, 'description' => 'Physical cash on hand'],
            ['name' => 'Bank', 'code' => WalletTypeCode::Bank->value, 'description' => 'Bank account'],
            ['name' => 'Savings', 'code' => WalletTypeCode::Savings->value, 'description' => 'Savings account'],
            ['name' => 'Credit', 'code' => WalletTypeCode::Credit->value, 'description' => 'Credit card / line of credit'],
        ];

        foreach ($types as $type) {
            WalletType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
