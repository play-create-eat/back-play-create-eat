<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\InvitationTemplate;
use App\Models\Package;
use App\Models\SlideshowImage;
use App\Models\Table;
use App\Models\TableBooking;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Symfony\Component\HttpFoundation\Response;

class CelebrationController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'completed' => 'boolean',
        ]);

        $query = Celebration::with('child', 'package', 'theme', 'menuItems', 'cake', 'modifierOptions', 'slideshow')
            ->where('user_id', auth()->guard('sanctum')->user()->id);

        if ($request->filled('completed')) {
            $query->where('completed', $request->boolean('completed'));
        }

        return response()->json($query->orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $celebrations = Celebration::where('user_id', auth()->guard('sanctum')->user()->id)
            ->where('completed', false)
            ->get();

        foreach ($celebrations as $celebration) {
            $celebration->delete();
        }

        $validated = $request->validate(['child_id' => 'required|exists:children,id']);

        $celebration = Celebration::create([
            'user_id'      => auth()->guard('sanctum')->user()->id,
            'child_id'     => $validated['child_id'],
            'current_step' => 1
        ]);

        return response()->json($celebration, Response::HTTP_CREATED);
    }

    public function package(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'package_id'   => 'required|exists:packages,id',
            'current_step' => 'required|integer'
        ]);

        $package = Package::findOrFail($validated['package_id']);

        $celebration->update([
            'package_id'   => $validated['package_id'],
            'price'        => Carbon::today()->isWeekend() ? $package->weekend_price : $package->weekday_price,
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
            'current_step'   => $validated['current_step']
        ]);

        return response()->json($celebration);
    }

    public function slot(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'datetime'     => 'required|date_format:Y-m-d H:i',
            'current_step' => 'required|integer'
        ]);

        $celebration->update([
            'celebration_date' => $validated['datetime'],
            'current_step'     => $validated['current_step']
        ]);

        return response()->json($celebration);
    }

    public function theme(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'theme_id'     => 'required|exists:themes,id',
            'current_step' => 'required|integer'
        ]);

        $celebration->update(['theme_id' => $validated['theme_id']]);

        return response()->json($celebration);
    }

    public function cake(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'cake_id'      => 'required|exists:cakes,id',
            'cake_weight'  => 'required|numeric',
            'current_step' => 'required|integer'
        ]);

        $celebration->update([
            'cake_id'      => $validated['cake_id'],
            'cake_weight'  => $validated['cake_weight'],
            'current_step' => $validated['current_step']
        ]);

        return response()->json($celebration);
    }

    public function menu(Request $request, Celebration $celebration)
    {
        // TODO: Attach and make request to attach parents menu to celebration
        $validated = $request->validate([
            'menu_items'                         => ['required', 'array'],
            'menu_items.*.menu_item_id'          => ['required', 'exists:menu_items,id'],
            'menu_items.*.quantity'              => ['required', 'integer', 'min:1'],
            'menu_items.*.modifier_option_ids'   => ['nullable', 'array'],
            'menu_items.*.modifier_option_ids.*' => ['exists:modifier_options,id'],
            'current_step'                       => ['required', 'integer'],
        ]);

        $celebration->menuItems()->detach();
        $celebration->modifierOptions()->detach();

        foreach ($validated['menu_items'] as $item) {
            $celebration->menuItems()->attach($item['menu_item_id'], [
                'quantity'   => $item['quantity'],
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
        // TODO: add attach to package by time (get duration hours)

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

    public function photographer(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'photographer' => 'required|boolean',
            'current_step' => 'required|integer'
        ]);

        $celebration->update($validated);

        return response()->json($celebration);
    }

    public function album(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'photo_album'  => 'required|boolean',
            'current_step' => 'required|integer'
        ]);

        $celebration->update($validated);

        return response()->json($celebration);
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function slideshow(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'photos.*'     => 'required|image|mimes:jpg,jpeg,png|max:20000',
            'current_step' => 'required|integer'
        ]);

        $slideshow = SlideshowImage::firstOrCreate(['celebration_id' => $celebration->id]);

        if ($slideshow->getMedia('slideshow_images')->count() >= 20) {
            return response()->json(['message' => 'Maximum 20 photos allowed.'], 400);
        }

        foreach ($request->file('photos') as $photo) {
            $slideshow->addMedia($photo)->toMediaCollection('slideshow_images');
        }

        $celebration->update(['current_step' => $validated['current_step']]);

        return response()->json(['message' => 'Photos uploaded successfully!', 'images' => $slideshow->getMedia('slideshow_images')]);
    }

    public function confirm(Celebration $celebration)
    {
        return response()->json($celebration->load('child', 'cake', 'package', 'theme', 'menuItems'));
    }

    public function pay(Request $request, Celebration $celebration)
    {
        // TODO: add request for rest payment for celebration
        $validated = $request->validate([
            'amount' => 'required|numeric'
        ]);

        $family = auth()->guard('sanctum')->user()->family;

        $mainWallet = $family->main_wallet;
        $cashbackWallet = $family->loyalty_wallet;

        if ($mainWallet->balance < $validated['amount']) {
            return response()->json(['message' => 'Insufficient funds in main wallet.'], 400);
        }

        try {
            $mainWallet->withdraw($validated['amount'], ['description' => "Partial payment for celebration $celebration->id."]);
            $celebration->update(['paid_amount' => $validated['amount']]);
        } catch (ExceptionInterface $e) {
            Log::error($e->getMessage());
            return response()->json(['message' => 'Failed to withdraw funds from main wallet.'], 400);
        }

        $cashbackPercent = $celebration->package->cashback_percentage;

        $cashback = round($validated['amount'] * ($cashbackPercent / 100), 2);

        if ($cashback > 0) {
            try {
                $cashbackWallet->deposit($cashback, ['description' => "Cashback from payment for celebration $celebration->id."]);
            } catch (ExceptionInterface $e) {
                Log::error($e->getMessage());
                return response()->json(['message' => 'Failed to deposit cashback to loyalty wallet.'], 400);
            }
        }

        return response()->json([
            'message'          => 'Payment successful',
            'paid_amount'      => $validated['amount'],
            'cashback_earned'  => $cashback,
            'wallet_balance'   => $mainWallet->balance,
            'cashback_balance' => $cashbackWallet->balance,
        ]);
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
