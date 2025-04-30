<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\PartyInvitationTemplate;
use App\Services\InvitationService;
use Exception;
use Illuminate\Http\Request;

class PartyInvitationController extends Controller
{
    public function __construct(protected InvitationService $invitationService)
    {
    }

    public function templates()
    {
        return response()->json($this->invitationService->templates());
    }

    public function generate(Request $request, Celebration $celebration, PartyInvitationTemplate $template)
    {
        try {
            $invitationUrl = $this->invitationService->generate($template, $celebration);

            return response()->json([
                'invitation_url' => $invitationUrl,
            ]);
        } catch (Exception $exception) {
            return response()->json($exception->getMessage(), 500);
        }
    }
}
