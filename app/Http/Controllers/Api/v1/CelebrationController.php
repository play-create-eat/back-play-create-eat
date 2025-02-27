<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
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

        $celebration->update(['package_id' => $validated['package_id']]);

        return response()->json($celebration);
    }

    public function guestsCount(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'children_count' => 'required|integer',
            'parents_count'  => 'required|integer'
        ]);

        $celebration->update([
            'children_count' => $validated['children_count'],
            'parents_count'  => $validated['parents_count']
        ]);

        return response()->json($celebration);
    }

    public function slot(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'datetime'       => 'required|date_format:Y-m-d H:i:s',
        ]);

        $celebration->update([
            'celebration_date' => $validated['datetime']
        ]);

        return response()->json($celebration);
    }

    public function theme(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'theme_id'       => 'required|exists:themes,id'
        ]);

        $celebration->update(['theme_id' => $validated['theme_id']]);

        return response()->json($celebration);
    }

    public function cake(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'cake_id'        => 'required|exists:cakes,id',
            'cake_weight'    => 'required|numeric'
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
            'menu_id'        => 'required|exists:menus,id'
        ]);

        $celebration->update(['menu_id' => $validated['menu_id']]);

        return response()->json($celebration);
    }

    public function availableSlots(Request $request)
    {
        $validated = $request->validate(['date' => 'required|date|after_or_equal:today']);
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

        $availableSlots = array_diff(array_keys($allSlots), $reservedSlots);

        $dayFull = empty($availableSlots);

        return response()->json([
            'date'            => $date->toDateString(),
            'available_slots' => array_values($availableSlots),
            'reserved_slots'  => array_values($reservedSlots),
            'day_full'        => $dayFull
        ]);

    }
}
