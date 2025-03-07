<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\Package;
use App\Models\Table;
use App\Models\TableBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class CelebrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate(['child_id' => 'required|exists:children,id']);

        $celebration = Celebration::create([
            'user_id'  => auth()->guard('sanctum')->user()->id,
            'child_id' => $validated['child_id']
        ]);

        return response()->json($celebration, Response::HTTP_CREATED);
    }

    public function package(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id'
        ]);

        $package = Package::findOrFail($validated['package_id']);

        $celebration->update([
            'package_id' => $validated['package_id'],
            'price'      => Carbon::today()->isWeekend() ? $package->weekend_price : $package->weekday_price
        ]);

        return response()->json($celebration);
    }

    public function guestsCount(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'children_count' => 'required', 'integer',
            'parents_count'  => 'required', 'integer'
        ]);

        $minChildren = $celebration->package->min_children;

        if ($validated['children_count'] < $minChildren) {
            return response()->json([
                'message' => "Minimum children count is $minChildren"
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $celebration->update([
            'children_count' => $validated['children_count'],
            'parents_count'  => $validated['parents_count']
        ]);

        return response()->json($celebration);
    }

    public function slot(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'datetime' => 'required|date_format:Y-m-d H:i',
        ]);

        $celebration->update([
            'celebration_date' => $validated['datetime']
        ]);

        return response()->json($celebration);
    }

    public function theme(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'theme_id' => 'required|exists:themes,id'
        ]);

        $celebration->update(['theme_id' => $validated['theme_id']]);

        return response()->json($celebration);
    }

    public function cake(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'cake_id'     => 'required|exists:cakes,id',
            'cake_weight' => 'required|numeric'
        ]);

        $celebration->update([
            'cake_id'     => $validated['cake_id'],
            'cake_weight' => $validated['cake_weight']
        ]);

        return response()->json($celebration);
    }

    public function menu(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'items'                => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity'     => 'required|integer|min:1'
        ]);

        $celebration->menuItems()->detach();

        foreach ($validated['items'] as $item) {
            $celebration->menuItems()->attach($item['menu_item_id'], ['quantity' => $item['quantity']]);
        }

        $celebration->update([
            'price' => $celebration->price + $celebration->calculateMenuPrice()
        ]);

        return response()->json($celebration->load('menuItems'));
    }

    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'date'           => ['required', 'date', 'after_or_equal:today'],
            'children_count' => ['required', 'integer', 'min:1']
        ]);
        $date = Carbon::parse($validated['date']);
        $allSlots = [
            '11:00 AM' => ['start' => '10:30 AM', 'end' => '2:00 PM'],
            '2:00 PM'  => ['start' => '1:30 PM', 'end' => '5:00 PM'],
            '5:00 PM'  => ['start' => '4:30 PM', 'end' => '8:00 PM']
        ];

        $bookings = Celebration::whereDate('celebration_date', $date)->get();

        $reservedSlots = [];
        foreach ($bookings as $booking) {
            $startTime = Carbon::parse($booking->celebration_date);
            foreach ($allSlots as $slot => $times) {
                if ($startTime->between(Carbon::parse($times['start']), Carbon::parse($times['end']))) {
                    $reservedSlots[] = $slot;
                }
            }
        }

        $availableSlots = [];

        foreach ($allSlots as $slot => $times) {
            if (!in_array($slot, $reservedSlots)) {
                if ($this->checkTableAvailability($validated['children_count'])) {
                    $availableSlots[] = $slot;
                }
            }
        }

        return response()->json([
            'date'            => $date->toDateString(),
            'available_slots' => array_values($availableSlots),
            'reserved_slots'  => array_values($reservedSlots),
        ]);
    }

    private function checkTableAvailability($childrenCount)
    {
        if ($childrenCount < 15) {
            return Table::where('capacity', 15)
                ->where('status', 'available')
                ->exists();
        } elseif ($childrenCount >= 15 && $childrenCount <= 30) {
            $table3 = Table::where('name', 'Table 3')
                ->where('status', 'available')
                ->exists();

            $table4 = Table::where('name', 'Table 4')
                ->where('status', 'available')
                ->exists();

            return $table3 && $table4;
        }

        return false;
    }

    private function assignTable(Celebration $celebration)
    {
        if ($celebration->children_count < 15) {
            $table = Table::where('capacity', 15)
                ->where('status', 'available')
                ->first();

            if ($table) {
                return $this->bookTable($celebration, [$table]);
            }
        }

        if ($celebration->children_count >= 15 && $celebration->children_count <= 30) {
            $table3 = Table::where('name', 'Table 3')->where('status', 'available')->first();
            $table4 = Table::where('name', 'Table 4')->where('status', 'available')->first();

            if ($table3 && $table4) {
                return $this->bookTable($celebration, [$table3, $table4]);
            }
        }

        return ['status' => 'error', 'message' => 'No tables available'];
    }

    private function bookTable(Celebration $celebration, $tables)
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
}
