<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Timeline;

class TimelineController extends Controller
{
    public function index()
    {
        return response()->json(Timeline::all());
    }
}
