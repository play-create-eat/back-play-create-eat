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
            'child_id' => $validated['child_id'],
            'current_step' => 1
        ]);

        return response()->json($celebration, Response::HTTP_CREATED);
    }

    public function package(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
            'current_step' => 'required|integer'
        ]);

        $package = Package::findOrFail($validated['package_id']);

        $celebration->update([
            'package_id' => $validated['package_id'],
            'price'      => Carbon::today()->isWeekend() ? $package->weekend_price : $package->weekday_price,
            'current_step' => $validated['current_step']
        ]);

        return response()->json($celebration);
    }

    public function guestsCount(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'children_count' => 'required', 'integer',
            'parents_count'  => 'required', 'integer',
            'current_step'   => 'required', 'integer'
        ]);

        $minChildren = $celebration->package->min_children;

        if ($validated['children_count'] < $minChildren) {
            return response()->json([
                'message' => "Minimum children count is $minChildren"
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $celebration->update([
            'children_count' => $validated['children_count'],
            'parents_count'  => $validated['parents_count'],
            'current_step' => $validated['current_step']
        ]);

        return response()->json($celebration);
    }

    public function slot(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'datetime' => 'required|date_format:Y-m-d H:i',
            'current_step' => 'required|integer'
        ]);

        $celebration->update([
            'celebration_date' => $validated['datetime'],
            'current_step' => $validated['current_step']
        ]);

        return response()->json($celebration);
    }

    public function theme(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'theme_id' => 'required|exists:themes,id',
            'current_step' => 'required|integer'
        ]);

        $celebration->update(['theme_id' => $validated['theme_id']]);

        return response()->json($celebration);
    }

    public function cake(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'cake_id'     => 'required|exists:cakes,id',
            'cake_weight' => 'required|numeric',
            'current_step' => 'required|integer'
        ]);

        $celebration->update([
            'cake_id'     => $validated['cake_id'],
            'cake_weight' => $validated['cake_weight'],
            'current_step' => $validated['current_step']
        ]);

        return response()->json($celebration);
    }

    public function menu(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'menu_items' => ['required', 'array'],
            'menu_items.*.menu_item_id' => ['required', 'exists:menu_items,id'],
            'menu_items.*.quantity' => ['required', 'integer', 'min:1'],
            'menu_items.*.modifier_option_ids' => ['nullable', 'array'],
            'menu_items.*.modifier_option_ids.*' => ['exists:modifier_options,id'],
            'current_step' => ['required', 'integer'],
        ]);

        $celebration->menuItems()->detach();
        $celebration->modifierOptions()->detach();

        foreach ($validated['menu_items'] as $item) {
            $celebration->menuItems()->attach($item['menu_item_id'], [
                'quantity' => $item['quantity'],
                'child_name' => $item['child_name'] ?? null,
            ]);

            if (!empty($item['modifier_option_ids'])) {
                foreach ($item['modifier_option_ids'] as $modifierOptionId) {
                    $celebration->modifierOptions()->attach($modifierOptionId);
                }
            }
        }

        $celebration->update(['current_step' => $validated['current_step']]);

        return response()->json(['message' => 'Menu and modifiers attached to celebration.']);
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

    public function photographerAndAlbum(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'photographer' => 'required|boolean',
            'photo_album'  => 'required|boolean',
            'current_step' => 'required|integer'
        ]);
        $celebration->update($validated);

        return response()->json($celebration);
    }

    public function invitation(Celebration $celebration)
    {
        return view('invitations.third-type', ['celebration' => $celebration]);
    }
}
