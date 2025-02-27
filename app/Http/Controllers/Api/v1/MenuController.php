<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Menu;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::with('meals.options')->get();
        return response()->json($menus);
    }

    public function show(Menu $menu)
    {
        return response()->json($menu->load('meals.options'));
    }
}
