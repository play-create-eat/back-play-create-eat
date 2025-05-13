<?php

namespace App\Http\Controllers\Api\v1\Internal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\Internal\PassResource;
use App\Models\Pass;
use App\Services\PassService;
use Illuminate\Http\Request;

class PassController extends Controller
{
    public function scan(Request $request)
    {
        $payload = $request->validate([
            'serial' => ['required', 'exists:passes,serial'],
            'product_type' => ['required', 'exists:product_types,id'],
        ]);

        $pass = app(PassService::class)->scan(
            serial: $payload['serial'],
            productTypeId: $payload['product_type']
        );

        return new PassResource($pass->load(['children', 'transfer.deposit']));
    }

    public function info(Request $request)
    {
        $payload = $request->validate([
            'serial' => ['required', 'exists:passes,serial'],
        ]);

        $pass = Pass::with(['children', 'transfer.deposit'])
            ->where('serial', $payload['serial'])->firstOrFail();

        return new PassResource($pass);
    }
}
