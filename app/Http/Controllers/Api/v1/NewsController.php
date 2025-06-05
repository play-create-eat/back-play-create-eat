<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(News::with('media')->latest()->get());
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        return response()->json($news->load('media'));
    }
}
