<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Celebration;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService)
    {
    }

    public function getAvailableTimeSlots(Celebration $celebration, Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        $availableSlots = $this->bookingService->getAvailableTimeSlots(
            $request->date,
            $celebration->package
        );

        return response()->json([
            'success' => true,
            'data'    => $availableSlots,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'celebration_package_id' => 'required|exists:celebration_packages,id',
            'child_name'             => 'required|string|max:255',
            'children_count'         => 'required|integer|min:1|max:30',
            'start_time'             => 'required|date_format:Y-m-d H:i:s|after:now',
            'special_requests'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }
    }

    public function index(): AnonymousResourceCollection
    {
        $bookings = Booking::where('user_id', auth()->id())
            ->orderBy('start_time', 'desc')
            ->with(['tables', 'celebrationPackage'])
            ->get();

        return BookingResource::collection($bookings);
    }

    public function show(Booking $booking): JsonResponse
    {
        if ($booking->user_id !== auth()->guard('sanctum')->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => new BookingResource($booking->load('tables', 'celebrationPackage')),
        ]);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        if ($booking->user_id !== auth()->guard('sanctum')->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        try {
            $this->bookingService->cancelBooking($booking);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
