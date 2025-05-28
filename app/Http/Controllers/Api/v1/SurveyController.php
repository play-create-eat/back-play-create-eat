<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SurveyController extends Controller
{
    /**
     * Store a new survey response.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'play.interesting' => 'sometimes|boolean',
            'play.safe' => 'sometimes|boolean',
            'play.staffFriendly' => 'sometimes|boolean',
            'create.activitiesInteresting' => 'sometimes|boolean',
            'create.staffFriendly' => 'sometimes|boolean',
            'eat.likedFood' => 'sometimes|string|in:yes,no,cannot_judge',
            'eat.likedDrinks' => 'sometimes|string|in:yes,no,cannot_judge',
            'eat.likedPastry' => 'sometimes|string|in:yes,no,cannot_judge',
            'eat.teamFriendly' => 'sometimes|string|in:yes,no,cannot_judge',
            'conclusion.suggestions' => 'sometimes|string|max:1000',
        ]);

        $surveyData = [
            'play_interesting' => $request->input('play.interesting'),
            'play_safe' => $request->input('play.safe'),
            'play_staff_friendly' => $request->input('play.staffFriendly'),
            'create_activities_interesting' => $request->input('create.activitiesInteresting'),
            'create_staff_friendly' => $request->input('create.staffFriendly'),
            'eat_liked_food' => $request->input('eat.likedFood'),
            'eat_liked_drinks' => $request->input('eat.likedDrinks'),
            'eat_liked_pastry' => $request->input('eat.likedPastry'),
            'eat_team_friendly' => $request->input('eat.teamFriendly'),
            'conclusion_suggestions' => $request->input('conclusion.suggestions'),
            'ip_address' => $request->ip(),
        ];

        $surveyData = array_filter($surveyData, function ($value) {
            return $value !== null;
        });

        $survey = Survey::create($surveyData);

        return response()->json([
            'message' => 'Survey submitted successfully',
            'data' => $survey
        ], 201);
    }

    /**
     * Get all survey responses (for admin use).
     */
    public function index(): JsonResponse
    {
        $surveys = Survey::orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $surveys
        ]);
    }

    /**
     * Get a specific survey response.
     */
    public function show(Survey $survey): JsonResponse
    {
        return response()->json([
            'data' => $survey
        ]);
    }
}
