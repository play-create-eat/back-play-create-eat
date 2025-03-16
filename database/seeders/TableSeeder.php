<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            ['name' => 'Table 1', 'capacity' => 15, 'status' => 'available'],
            ['name' => 'Table 2', 'capacity' => 15, 'status' => 'available'],
            ['name' => 'Table 3', 'capacity' => 30, 'status' => 'available'],
            ['name' => 'Table 4', 'capacity' => 30, 'status' => 'available'],
        ];

        foreach ($tables as $table) {
            Table::create($table);
        }
    }
}
