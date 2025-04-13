<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Celebration;
use App\Models\Package;
use App\Models\SlideshowImage;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CelebrationController extends Controller
{

    public function index(Request $request)
    {
        // TODO: delete all uncompleted celebrations just leave 1
        $request->validate([
            'completed' => 'boolean',
            'unpaid'    => 'boolean',
        ]);

        $query = Celebration::with([
            'child',
            'package',
            'cake',
            'theme',
            'cart.items.menuItem.tags',
            'cart.items.menuItem.type',
            'cart.items.menuItem.modifierGroups.options',
            'cart.items.modifiers.modifierOption',
            'invitation',
            'slideshow',
        ])->where('user_id', auth()->guard('sanctum')->user()->id);

        if ($request->filled('completed')) {
            $query->where('completed', $request->boolean('completed'));
        }

        if ($request->filled('unpaid')) {
            $query->where('paid_amount', '<', 'total_amount');
        }

        return response()->json($query->orderByDesc('celebration_date')->get());
    }

    public function show(Celebration $celebration)
    {
        $celebration->load([
            'child',
            'package',
            'cake',
            'theme',
            'cart.items.menuItem.tags',
            'cart.items.menuItem.type',
            'cart.items.menuItem.modifierGroups.options',
            'cart.items.modifiers.modifierOption',
            'invitation',
            'slideshow',

        ]);

        return response()->json($celebration);
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
            'current_step' => 1,
            'min_amount'   => 100000
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
            'current_step' => $validated['current_step']
        ]);

        return response()->json($celebration->load('package', 'package.timelines'));
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

    public function slots(Request $request, Celebration $celebration, BookingService $bookingService)
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $slots = $bookingService->getAvailableTimeSlots(
            $validated['date'],
            $celebration->package,
            $celebration->children_count
        );

        return response()->json([
            'date'     => $validated['date'],
            'duration' => $celebration->package->duration_hours,
            'slots'    => $slots,
        ]);
    }

    public function slot(Request $request, Celebration $celebration, BookingService $bookingService)
    {
        $validated = $request->validate([
            'datetime' => ['required', 'date', 'after:now'],
        ]);

        try {
            $booking = $bookingService->createBooking([
                'user_id'          => auth()->guard('sanctum')->user()->id,
                'celebration_id'   => $celebration->id,
                'package_id'       => $celebration->package->id,
                'child_name'       => $celebration->child->first_name,
                'children_count'   => $celebration->children_count,
                'start_time'       => $validated['datetime'],
                'special_requests' => '',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data'    => new BookingResource($booking->load('tables')),
            ], 201);
        } catch (Exception|Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
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

    /**
     * @throws Throwable
     */
    public function menu(Request $request, Celebration $celebration)
    {
        $validated = $request->validate([
            'menu_items'                         => 'required|array',
            'menu_items.*.menu_item_id'          => 'required|exists:menu_items,id',
            'menu_items.*.quantity'              => 'required|integer|min:1',
            'menu_items.*.audience'              => 'required|in:children,parents',
            'menu_items.*.modifier_option_ids'   => 'nullable|array',
            'menu_items.*.modifier_option_ids.*' => 'exists:modifier_options,id',
            'current_step'                       => 'required|integer'
        ]);


        DB::transaction(function () use ($validated, $celebration) {
            foreach ($validated['menu_items'] as $item) {
                $celebration->menuItems()->attach($item['menu_item_id'], [
                    'quantity'   => $item['quantity'],
                    'audience'   => $item['audience'],
                    'child_name' => $item['child_name'] ?? null,
                ]);

                if (!empty($item['modifier_option_ids'])) {
                    foreach ($item['modifier_option_ids'] as $optionId) {
                        $celebration->modifierOptions()->syncWithoutDetaching($optionId);
                    }
                }
            }
        });

        $celebration->update(['current_step' => $validated['current_step']]);
        $celebration->load([
            'menuItems.tags',
            'menuItems.type',
            'menuItems.category',
            'menuItems.modifierGroups',
            'menuItems.modifierGroups.options',
        ]);

        return response()->json([
            'message' => 'Menu added to celebration successfully.',
            'menu'    => $celebration->menuItems
                ->groupBy(fn($item) => $item->pivot->audience)
                ->map(function ($items) {
                    return $items->map(function ($item) {
                        return [
                            'id'             => $item->id,
                            'name'           => $item->name,
                            'price'          => $item->price,
                            'audience'       => $item->pivot->audience,
                            'quantity'       => $item->pivot->quantity,
                            'image'          => $item->getFirstMediaUrl('menu_item_images'),
                            'tags'           => $item->tags->map(fn($tag) => [
                                'id'    => $tag->id,
                                'name'  => $tag->name,
                                'color' => $tag->color
                            ]),
                            'modifierGroups' => $item->modifierGroups->map(function ($group) {
                                return [
                                    'id'        => $group->id,
                                    'title'     => $group->title,
                                    'minAmount' => $group->min_amount,
                                    'maxAmount' => $group->max_amount,
                                    'required'  => $group->required,
                                    'options'   => $group->options->map(fn($opt) => [
                                        'id'            => $opt->id,
                                        'name'          => $opt->name,
                                        'price'         => $opt->price,
                                        'nutritionInfo' => $opt->nutrition_info
                                    ])
                                ];
                            })
                        ];
                    });
                }),
        ]);
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

        $slideshow->refresh();

        $celebration->update(['current_step' => $validated['current_step']]);

        return response()->json([
            'message' => 'Photos uploaded successfully!',
            'images'  => $slideshow->getMedia('slideshow_images')->map(function ($media) {
                return [
                    'id'  => $media->id,
                    'url' => $media->getUrl()
                ];
            }),
        ]);
    }

    public function timelines(Celebration $celebration)
    {
        return response()->json($celebration->package->timelines);
    }

    public function confirm(Celebration $celebration)
    {
        return response()->json($celebration->load('child', 'cake', 'package', 'theme', 'menuItems'));
    }
}
