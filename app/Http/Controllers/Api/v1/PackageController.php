<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        return response()->json(Package::with('features')->get());
    }

    public function show(Package $package)
    {
        return response()->json($package->load('features'));
    }
}
