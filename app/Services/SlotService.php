<?php

namespace App\Services;

use App\Models\Celebration;
use Carbon\Carbon;

class SlotService
{
    protected array $workingHours = ['start' => '10:00', 'end' => '20:00'];
    protected int $bufferTime = 30;


    public function __construct(protected TableService $tableService)
    {
    }

    public function getAvailableSlots(string $date, int $count, int $duration): array
    {
        $celebrationDate = Carbon::parse($date)->startOfDay();

        $bookings = Celebration::whereDate('celebration_date', $celebrationDate)->with('package')->get();

        $slots = [];
        $startTime = Carbon::parse($this->workingHours['start'], config('app.timezone'))
            ->setDateFrom($celebrationDate)
            ->addMinutes($this->bufferTime);

        $endTime = Carbon::parse($this->workingHours['end'], config('app.timezone'))
            ->setDateFrom($celebrationDate)
            ->subMinutes($this->bufferTime);

        $celebrationBlock = $duration * 60 + ($this->bufferTime * 2);

        while ($startTime->lt($endTime)) {
            $slotStart = $startTime->copy();
            $slotEnd = $slotStart->copy()->addMinutes($celebrationBlock);

            $conflict = $bookings->first(function ($booking) use ($slotStart, $slotEnd) {
                $bookingStart = Carbon::parse($booking->celebration_date)->subMinutes($this->bufferTime);
                $bookingDuration = $booking->package->duration_hours ?? 2;
                $bookingEnd = Carbon::parse($booking->celebration_date)
                    ->addMinutes($bookingDuration * 60 + $this->bufferTime);

                return $slotStart->lt($bookingEnd) && $slotEnd->gt($bookingStart);
            });

            if (!$conflict && $this->tableService->isAvailable($count)) {
                $slots[] = $slotStart->format('H:i');
            }

            $startTime->addMinutes(30);
        }

        return $slots;
    }
}
