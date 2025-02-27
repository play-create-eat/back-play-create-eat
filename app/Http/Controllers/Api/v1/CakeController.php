<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Cake;

class CakeController extends Controller
{
    public function index()
    {
        return response()->json(Cake::all());
    }

    public function show(Cake $cake)
    {
        return response()->json($cake);
    }
}
