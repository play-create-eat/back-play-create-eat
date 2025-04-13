<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\CelebrationFeature;

class CelebrationFeatureController extends Controller
{
    public function index()
    {
        return response()->json(CelebrationFeature::all());
    }
}
