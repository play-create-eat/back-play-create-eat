<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function store()
    {
        return view('invitations.first-type');
    }
}
