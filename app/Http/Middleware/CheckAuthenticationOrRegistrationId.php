<?php

namespace App\Http\Middleware;

use App\Models\PartialRegistration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthenticationOrRegistrationId
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        $registrationId = $request->input('registration_id');
        if ($registrationId && PartialRegistration::where('id', $registrationId)->exists()) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
