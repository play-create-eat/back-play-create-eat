<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Theme;

class ThemeController extends Controller
{
    public function index()
    {
        $themes = Theme::all();
        return response()->json($themes);
    }

    public function show(Theme $theme)
    {
        return response()->json($theme->load('media'));
    }
}
