<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Theme;

class ThemeController extends Controller
{
    public function index()
    {
        $themes = Theme::with(['media'])->get()->groupBy(['type', 'age']);
        return response()->json($themes);
    }

    public function show(Theme $theme)
    {
        return response()->json($theme->load('media'));
    }
}
