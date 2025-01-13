<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/documents",
     *     summary="Sign a document",
     *     tags={"Documents"},
     *     @OA\Response(
     *         response=200,
     *         description="Document signed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Document signed successfully.")
     *         )
     *     )
     * )
     */
    public function store()
    {
        return response()->json(['message' => 'Document signed successfully.']);
    }
}
