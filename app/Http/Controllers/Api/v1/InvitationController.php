<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Mail\InviteMemberMail;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Mail;
use Random\RandomException;

class InvitationController extends Controller
{
    public function invite(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        $user = $request->user();
        $familyId = $user->family_id;

        if (!$familyId) {
            return response()->json(['message' => 'User is not part of a family.'], 400);
        }

        $code = rand(100000, 999999);

        Invitation::create([
           'code' => $code,
           'email' => $request->email,
           'family_id' => $familyId,
           'creator_id' => $request->user()->id,
        ]);

        Mail::to($request->email)->send(new InviteMemberMail($code));

        return response()->json(['message' => 'Invitation sent successfully.']);
    }
}
