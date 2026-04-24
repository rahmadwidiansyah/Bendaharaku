<?php
namespace Database\Seeders;

use App\Models\TransactionType;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Income', 'keyword' => 'in, +'],
            ['name' => 'Expense', 'keyword' => 'out, -'],
            ['name' => 'Transfer', 'keyword' => 'trf'],
            ['name' => 'Debt', 'keyword' => 'debt'],       
            ['name' => 'Receivable', 'keyword' => 'rec'],
        ];

        foreach ($types as $type) {
            TransactionType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
