<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            [
                'name'                      => 'Table 1',
                'capacity'                  => 15,
                'is_preferred_for_children' => false,
                'is_active'                 => true,
            ],
            [
                'name'                      => 'Table 2',
                'capacity'                  => 15,
                'is_preferred_for_children' => false,
                'is_active'                 => true,
            ],
            [
                'name'                      => 'Table 3',
                'capacity'                  => 15,
                'is_preferred_for_children' => true,
                'is_active'                 => true,
            ],
            [
                'name'                      => 'Table 4',
                'capacity'                  => 15,
                'is_preferred_for_children' => true,
                'is_active'                 => true,
            ],
        ];

        foreach ($tables as $table) {
            Table::updateOrCreate(
                ['name' => $table['name']],
                $table
            );
        }
    }
}
