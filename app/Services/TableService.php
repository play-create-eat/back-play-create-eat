<?php

namespace App\Services;

use App\Models\Celebration;
use App\Models\Table;
use App\Models\TableBooking;

class TableService
{
    public function assign(Celebration $celebration): array
    {
        $count = $celebration->children_count;

        if ($count < 15) {
            return $this->trySingleTableBooking($celebration);
        }

        if ($count <= 30) {
            return $this->tryDoubleTableBooking($celebration);
        }

        return ['status' => 'error', 'message' => 'Booking exceeds capacity limits'];
    }

    protected function trySingleTableBooking(Celebration $celebration): array
    {
        foreach (['Table 1', 'Table 2', 'Table 3', 'Table 4'] as $name) {
            $table = Table::where('name', $name)->where('status', 'available')->first();
            if ($table) {
                return $this->book($celebration, [$table]);
            }
        }

        return ['status' => 'error', 'message' => 'No table available.'];
    }

    protected function book(Celebration $celebration, array $tables): array
    {
        foreach ($tables as $table) {
            TableBooking::create([
                'celebration_id' => $celebration->id,
                'table_id'       => $table->id
            ]);
            $table->update(['status' => 'booked']);
        }

        return ['status' => 'success', 'tables' => array_map(fn($table) => $table->name, $tables)];
    }

    protected function tryDoubleTableBooking(Celebration $celebration): array
    {
        $table3 = Table::where('name', 'Table 3')->where('status', 'available')->first();
        $table4 = Table::where('name', 'Table 4')->where('status', 'available')->first();

        if ($table3 && $table4) {
            return $this->book($celebration, [$table3, $table4]);
        }

        return ['status' => 'error', 'message' => 'No tables available.'];
    }

    public function isAvailable(int $count): bool
    {
        if ($count < 15) {
            return Table::where('capacity', '<=', 15)
                ->where('status', 'available')
                ->exists();
        }

        if ($count <= 30) {
            $table3 = Table::where('name', 'Table 3')->where('status', 'available')->exists();
            $table4 = Table::where('name', 'Table 4')->where('status', 'available')->exists();

            return $table3 && $table4;
        }

        return false;
    }
}
