<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DailyActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DailyActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DailyActivity::query();

        if ($request->has('day')) {
            $query->forDay($request->input('day'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->input('location') . '%');
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $activities = $query->ordered()->get();

        return response()->json([
            'data' => $activities,
            'message' => 'Daily activities retrieved successfully'
        ]);
    }

    public function show(DailyActivity $dailyActivity): JsonResponse
    {
        return response()->json([
            'data' => $dailyActivity,
            'message' => 'Daily activity retrieved successfully'
        ]);
    }

    public function today(): JsonResponse
    {
        $today = Carbon::now()->format('l');

        $activities = DailyActivity::active()
            ->forDay($today)
            ->ordered()
            ->get();

        return response()->json([
            'data' => $activities,
            'day' => $today,
            'message' => 'Today\'s activities retrieved successfully'
        ]);
    }

    public function week(): JsonResponse
    {
        $weeklySchedule = [];
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($daysOfWeek as $day) {
            $weeklySchedule[$day] = DailyActivity::active()
                ->forDay($day)
                ->ordered()
                ->get();
        }

        return response()->json([
            'data' => $weeklySchedule,
            'message' => 'Weekly schedule retrieved successfully'
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = DailyActivity::active()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->values();

        return response()->json([
            'data' => $categories,
            'message' => 'Categories retrieved successfully'
        ]);
    }

    public function locations(): JsonResponse
    {
        $locations = DailyActivity::active()
            ->distinct()
            ->pluck('location')
            ->values();

        return response()->json([
            'data' => $locations,
            'message' => 'Locations retrieved successfully'
        ]);
    }
}
