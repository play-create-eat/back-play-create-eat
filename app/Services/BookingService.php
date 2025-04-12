<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Package;
use App\Models\Table;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BookingService
{
    /**
     * Get available time slots for a specific date and package
     */
    public function getAvailableTimeSlots(string $date, Package $package, int $childrenCount): array
    {
        Log::info('Getting available time slots', [
            'date'          => $date,
            'package_id'    => $package->id,
            'childrenCount' => $childrenCount
        ]);

        $duration = (int)$package->duration_hours;

        $workStartTime = Carbon::parse($date . ' 10:00');
        $workEndTime = Carbon::parse($date . ' 20:00');

        Log::debug('Work hours', [
            'workStartTime' => $workStartTime->format('Y-m-d H:i'),
            'workEndTime'   => $workEndTime->format('Y-m-d H:i')
        ]);

        $tablesCount = Table::where('is_active', true)->count();
        if ($tablesCount === 0) {
            Log::warning('No active tables found');
            return [];
        }

        $availableSlots = [];
        $currentSlot = clone $workStartTime;

        $slotCount = 0;
        while ($currentSlot < $workEndTime) {
            $setupStartTime = clone $currentSlot;
            $startTime = (clone $setupStartTime)->addMinutes(30);
            $endTime = (clone $startTime)->addHours($duration);
            $cleanupEndTime = (clone $endTime)->addMinutes(30);

            if ($cleanupEndTime > $workEndTime) {
                Log::debug('Slot extends beyond working hours, stopping');
                break;
            }

            $slotCount++;
            Log::debug("Checking slot #{$slotCount}", [
                'setupStartTime' => $setupStartTime->format('Y-m-d H:i'),
                'startTime'      => $startTime->format('Y-m-d H:i'),
                'endTime'        => $endTime->format('Y-m-d H:i'),
                'cleanupEndTime' => $cleanupEndTime->format('Y-m-d H:i')
            ]);

            if ($childrenCount <= 15) {
                if ($this->isAnyTableAvailableForSlot($setupStartTime, $cleanupEndTime)) {
                    $availableSlots[] = [
                        'start_time'       => $startTime->format('Y-m-d H:i'),
                        'end_time'         => $endTime->format('Y-m-d H:i'),
                        'setup_start_time' => $setupStartTime->format('Y-m-d H:i'),
                        'cleanup_end_time' => $cleanupEndTime->format('Y-m-d H:i'),
                    ];
                    Log::debug('Slot is available for small group, added to results');
                } else {
                    Log::debug('Slot is not available for small group, skipped');
                }
            } else if ($childrenCount <= 30) {
                if ($this->areTables3And4AvailableForSlot($setupStartTime, $cleanupEndTime)) {
                    $availableSlots[] = [
                        'start_time'       => $startTime->format('Y-m-d H:i'),
                        'end_time'         => $endTime->format('Y-m-d H:i:s'),
                        'setup_start_time' => $setupStartTime->format('Y-m-d H:i'),
                        'cleanup_end_time' => $cleanupEndTime->format('Y-m-d H:i'),
                    ];
                    Log::debug('Slot is available for large group, added to results');
                } else {
                    Log::debug('Slot is not available for large group, skipped');
                }
            } else {
                Log::warning('Children count exceeds maximum capacity (30)');
            }

            $currentSlot->addMinutes(30);
        }

        Log::info('Available slots found', ['count' => count($availableSlots)]);
        return $availableSlots;
    }

    private function isAnyTableAvailableForSlot(Carbon $startTime, Carbon $endTime): bool
    {
        Log::debug('Checking if any table is available for slot', [
            'start' => $startTime->format('Y-m-d H:i'),
            'end'   => $endTime->format('Y-m-d H:i')
        ]);

        $table1 = Table::where('name', 'Table 1')->where('is_active', true)->first();
        $table2 = Table::where('name', 'Table 2')->where('is_active', true)->first();
        $table3 = Table::where('name', 'Table 3')->where('is_active', true)->first();
        $table4 = Table::where('name', 'Table 4')->where('is_active', true)->first();

        $startTimeStr = $startTime->format('Y-m-d H:i');
        $endTimeStr = $endTime->format('Y-m-d H:i');

        $table1Available = $table1 ? $this->checkTableAvailabilityDirectly($table1->id, $startTimeStr, $endTimeStr) : false;
        $table2Available = $table2 ? $this->checkTableAvailabilityDirectly($table2->id, $startTimeStr, $endTimeStr) : false;
        $table3Available = $table3 ? $this->checkTableAvailabilityDirectly($table3->id, $startTimeStr, $endTimeStr) : false;
        $table4Available = $table4 ? $this->checkTableAvailabilityDirectly($table4->id, $startTimeStr, $endTimeStr) : false;

        Log::debug('Table availability for small group', [
            'table1' => $table1Available ? 'available' : 'unavailable',
            'table2' => $table2Available ? 'available' : 'unavailable',
            'table3' => $table3Available ? 'available' : 'unavailable',
            'table4' => $table4Available ? 'available' : 'unavailable',
        ]);

        $isAnyTableAvailable = $table1Available || $table2Available || $table3Available || $table4Available;

        Log::debug('Any table available: ' . ($isAnyTableAvailable ? 'Yes' : 'No'));

        return $isAnyTableAvailable;
    }

    /**
     * Check if a table is available directly using raw SQL for reliability
     */
    private function checkTableAvailabilityDirectly(int $tableId, string $startTime, string $endTime): bool
    {
        $overlappingBookings = DB::table('bookings')
            ->join('booking_table', 'bookings.id', '=', 'booking_table.booking_id')
            ->where('booking_table.table_id', '=', $tableId)
            ->where('bookings.status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('setup_start_time', '<', $endTime)
                    ->where('cleanup_end_time', '>', $startTime);
            })
            ->select('bookings.id', 'bookings.start_time', 'bookings.end_time')
            ->get();

        $available = $overlappingBookings->isEmpty();

        Log::debug("Table {$tableId} availability check", [
            'table_id'             => $tableId,
            'start_time'           => $startTime,
            'end_time'             => $endTime,
            'overlapping_bookings' => $overlappingBookings->count(),
            'available'            => $available
        ]);

        return $available;
    }

    private function areTables3And4AvailableForSlot(Carbon $startTime, Carbon $endTime): bool
    {
        Log::debug('Checking if Tables 3 and 4 are available for slot', [
            'start' => $startTime->format('Y-m-d H:i'),
            'end'   => $endTime->format('Y-m-d H:i')
        ]);

        $table3 = Table::where('name', 'Table 3')->where('is_active', true)->first();
        $table4 = Table::where('name', 'Table 4')->where('is_active', true)->first();

        $startTimeStr = $startTime->format('Y-m-d H:i');
        $endTimeStr = $endTime->format('Y-m-d H:i');

        if (!$table3 || !$table4) {
            Log::warning('Tables 3 or 4 do not exist or are not active');
            return false;
        }

        $table3Available = $this->checkTableAvailabilityDirectly($table3->id, $startTimeStr, $endTimeStr);
        $table4Available = $this->checkTableAvailabilityDirectly($table4->id, $startTimeStr, $endTimeStr);

        Log::debug('Tables 3 and 4 availability for large group', [
            'table3' => $table3Available ? 'available' : 'unavailable',
            'table4' => $table4Available ? 'available' : 'unavailable',
        ]);

        $areBothAvailable = $table3Available && $table4Available;

        Log::debug('Both Tables 3 and 4 available: ' . ($areBothAvailable ? 'Yes' : 'No'));

        return $areBothAvailable;
    }

    /**
     * Create a new booking using the specific table assignment logic
     * @throws Throwable
     */
    public function createBooking(array $data): Booking
    {
        Log::info('Creating booking', $data);

        $package = Package::findOrFail($data['package_id']);

        $startTime = Carbon::parse($data['start_time']);
        $endTime = (clone $startTime)->addHours((int)$package->duration_hours);
        $setupStartTime = (clone $startTime)->subMinutes(30);
        $cleanupEndTime = (clone $endTime)->addMinutes(30);

        $startTimeStr = $startTime->format('Y-m-d H:i');
        $endTimeStr = $endTime->format('Y-m-d H:i');
        $setupStartTimeStr = $setupStartTime->format('Y-m-d H:i');
        $cleanupEndTimeStr = $cleanupEndTime->format('Y-m-d H:i');

        Log::debug('Booking time calculations', [
            'startTime'      => $startTimeStr,
            'endTime'        => $endTimeStr,
            'setupStartTime' => $setupStartTimeStr,
            'cleanupEndTime' => $cleanupEndTimeStr
        ]);

        $table1 = Table::where('name', 'Table 1')->where('is_active', true)->first();
        $table2 = Table::where('name', 'Table 2')->where('is_active', true)->first();
        $table3 = Table::where('name', 'Table 3')->where('is_active', true)->first();
        $table4 = Table::where('name', 'Table 4')->where('is_active', true)->first();

        $table1Available = $table1 ? $this->checkTableAvailabilityDirectly($table1->id, $setupStartTimeStr, $cleanupEndTimeStr) : false;
        $table2Available = $table2 ? $this->checkTableAvailabilityDirectly($table2->id, $setupStartTimeStr, $cleanupEndTimeStr) : false;
        $table3Available = $table3 ? $this->checkTableAvailabilityDirectly($table3->id, $setupStartTimeStr, $cleanupEndTimeStr) : false;
        $table4Available = $table4 ? $this->checkTableAvailabilityDirectly($table4->id, $setupStartTimeStr, $cleanupEndTimeStr) : false;

        Log::debug('Table availability for booking', [
            'table1' => $table1Available ? 'available' : 'unavailable',
            'table2' => $table2Available ? 'available' : 'unavailable',
            'table3' => $table3Available ? 'available' : 'unavailable',
            'table4' => $table4Available ? 'available' : 'unavailable'
        ]);

        $numberOfPersons = $data['children_count'];
        $selectedTables = collect();

        if ($numberOfPersons <= 15) {
            Log::info('Applying logic for <= 15 persons');

            if ($table1Available && $table1) {
                $selectedTables->push($table1);
                Log::debug('Selected Table 1');
            } elseif ($table2Available && $table2) {
                $selectedTables->push($table2);
                Log::debug('Selected Table 2');
            } elseif ($table3Available && $table3) {
                $selectedTables->push($table3);
                Log::debug('Selected Table 3');
            } elseif ($table4Available && $table4) {
                $selectedTables->push($table4);
                Log::debug('Selected Table 4');
            } else {
                Log::error('No table available for booking < 15 persons');
                throw new Exception('No table available for this booking.');
            }
        } else if ($numberOfPersons <= 30) {
            Log::info('Applying logic for 15-30 persons');

            if ($table3Available && $table4Available && $table3 && $table4) {
                $selectedTables->push($table3);
                $selectedTables->push($table4);
                Log::debug('Selected Tables 3 and 4');
            } else {
                Log::error('Tables 3 and 4 not both available');
                throw new Exception('No tables available for 15-30 persons. Tables 3 and 4 are required but not available.');
            }
        } else {
            Log::error('Booking for more than 30 persons is not supported');
            throw new Exception('Booking for more than 30 persons is not supported.');
        }

        Log::info('Selected tables for booking', [
            'selectedTables' => $selectedTables->pluck('name')->toArray()
        ]);

        return DB::transaction(function () use ($data, $startTime, $endTime, $setupStartTime, $cleanupEndTime, $selectedTables) {
            $booking = Booking::create([
                'user_id'          => $data['user_id'],
                'package_id'       => $data['package_id'],
                'child_name'       => $data['child_name'],
                'children_count'   => $data['children_count'],
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'setup_start_time' => $setupStartTime,
                'cleanup_end_time' => $cleanupEndTime,
                'special_requests' => $data['special_requests'] ?? null,
                'status'           => 'pending',
            ]);

            Log::info('Created booking', ['booking_id' => $booking->id]);

            foreach ($selectedTables as $table) {
                $booking->tables()->attach($table->id);
                Log::debug("Attached Table $table->name (ID: $table->id) to booking $booking->id");
            }

            return $booking;
        });
    }

    /**
     * Cancel a booking
     * @throws Exception
     */
    public function cancelBooking(Booking $booking): bool
    {
        if (Carbon::now()->greaterThan($booking->start_time)) {
            throw new Exception('Cannot cancel a booking that has already started or completed.');
        }

        Log::info('Cancelling booking', ['booking_id' => $booking->id]);

        $booking->status = 'cancelled';
        return $booking->save();
    }

    /**
     * Get available tables for a given time period
     * This method is NOT used by the booking logic but is kept for API completeness
     */
    public function getAvailableTables(Carbon $startTime, Carbon $endTime): Collection
    {
        $startTimeStr = $startTime->format('Y-m-d H:i');
        $endTimeStr = $endTime->format('Y-m-d H:i');

        $allTables = Table::where('is_active', true)->get();

        Log::debug('Checking availability for all tables', [
            'total_tables' => $allTables->count(),
            'start_time'   => $startTimeStr,
            'end_time'     => $endTimeStr
        ]);

        $availableTables = $allTables->filter(function ($table) use ($startTimeStr, $endTimeStr) {
            $isAvailable = $this->checkTableAvailabilityDirectly($table->id, $startTimeStr, $endTimeStr);
            Log::debug("Table $table->name (ID: $table->id) availability: " . ($isAvailable ? 'available' : 'unavailable'));
            return $isAvailable;
        });

        Log::debug('Available tables', [
            'count'  => $availableTables->count(),
            'tables' => $availableTables->pluck('name')->toArray()
        ]);

        return $availableTables;
    }

    /**
     * Check if tables are available for a time slot based on specific booking logic
     */
    private function areTablesAvailableForSlot(Carbon $startTime, Carbon $endTime): bool
    {
        Log::debug('Checking tables availability for slot', [
            'start' => $startTime->format('Y-m-d H:i'),
            'end'   => $endTime->format('Y-m-d H:i')
        ]);

        $table1 = Table::where('name', 'Table 1')->where('is_active', true)->first();
        $table2 = Table::where('name', 'Table 2')->where('is_active', true)->first();
        $table3 = Table::where('name', 'Table 3')->where('is_active', true)->first();
        $table4 = Table::where('name', 'Table 4')->where('is_active', true)->first();

        $startTimeStr = $startTime->format('Y-m-d H:i');
        $endTimeStr = $endTime->format('Y-m-d H:i');

        $table1Available = $table1 ? $this->checkTableAvailabilityDirectly($table1->id, $startTimeStr, $endTimeStr) : false;
        $table2Available = $table2 ? $this->checkTableAvailabilityDirectly($table2->id, $startTimeStr, $endTimeStr) : false;
        $table3Available = $table3 ? $this->checkTableAvailabilityDirectly($table3->id, $startTimeStr, $endTimeStr) : false;
        $table4Available = $table4 ? $this->checkTableAvailabilityDirectly($table4->id, $startTimeStr, $endTimeStr) : false;

        Log::debug('Table availability results', [
            'table1' => $table1Available ? 'available' : 'unavailable',
            'table2' => $table2Available ? 'available' : 'unavailable',
            'table3' => $table3Available ? 'available' : 'unavailable',
            'table4' => $table4Available ? 'available' : 'unavailable',
        ]);

        $isAnyTableAvailable = $table1Available || $table2Available || $table3Available || $table4Available;

        $areTables3And4Available = $table3Available && $table4Available;

        $result = $isAnyTableAvailable || $areTables3And4Available;

        Log::debug('Slot availability result: ' . ($result ? 'AVAILABLE' : 'NOT AVAILABLE'), [
            'isAnyTableAvailable'     => $isAnyTableAvailable,
            'areTables3And4Available' => $areTables3And4Available
        ]);

        return $result;
    }
}
