<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\PandaDocService;
use Exception;
use Illuminate\Http\Request;
use Log;

class PandaDocController extends Controller
{
    public function __construct(protected PandaDocService $pandadoc)
    {
    }


    /**
 * @OA\Post(
 *     path="/api/v1/documents",
 *     summary="Create a document from template and prepare it for signing.",
 *     tags={"Documents"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="FIRST_NAME", type="string", example="John", description="Recipient's first name"),
 *             @OA\Property(property="LAST_NAME", type="string", example="Doe", description="Recipient's last name"),
 *             @OA\Property(property="PHONE_NUMBER", type="string", example="+971501234567", description="Recipient's phone number"),
 *             @OA\Property(property="EMAIL_ADDRESS", type="string", example="tech@playcreateeat.ae", description="Recipient's email address"),
 *             @OA\Property(property="PASSPORT_NUMBER", type="string", example="P1234567", description="Recipient's passport number"),
 *             @OA\Property(property="KIDS_ARRAY", type="string", example="Alex Malii from 30.05.2004", description="Additional information about kids.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Document created and ready for signing.",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Document is ready for signing."),
 *             @OA\Property(property="document_id", type="string", example="xyz789", description="The ID of the newly created document."),
 *             @OA\Property(property="signing_link", type="string", example="https://sign.pandadoc.com/signinglink", description="The link to sign the document.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Document creation or signing preparation failed.",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Document is not ready for signing.")
 *         )
 *     )
 * )
 */
public function create()
{
    $recipientData = [
        "FIRST_NAME"      => ["value" => "John"],
        "LAST_NAME"       => ["value" => "Doe"],
        "PHONE_NUMBER"    => ["value" => "+971501234567"],
        "EMAIL_ADDRESS"   => ["value" => "tech@playcreateeat.ae"],
        "PASSPORT_NUMBER" => ["value" => "P1234567"],
    ];

    $recipientData = [...$recipientData, 'KIDS_ARRAY' => ['value' => "Alex Malii from 30.05.2004"]];


    try {
        $document = $this->pandadoc->createDocumentFromTemplate("WVGshXQkryavX2rVoCwQf5", $recipientData);

            if ($document->getId()) {
                if ($this->pandadoc->checkDocumentStatus($document->getId())) {
                    $this->pandadoc->sendDocument($document->getId());
                    $response = $this->pandadoc->generateDocumentLink($document->getId());
                    $signingResponse = json_decode($response->getContent(), true);

                    return response()->json([...$signingResponse, 'document_id' => $document->getId()]);
                }

                return response()->json([...$signingResponse, 'document_id' => $document->getId()]);
            }

            return response()->json(['error' => 'Document is not ready for signing.'], 500);
        }
        return response()->json(['error' => 'Document creation failed.'], 500);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * @OA\Get(
     *     path="/api/v1/documents/status/{id}",
     *     summary="Check the status of a document",
     *     tags={"Documents"},
     *     security={{"Sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the document to check the status for",
     *         @OA\Schema(type="string", example="abc123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="sent", description="Current status of the document")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Document not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Document not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     * @throws Exception
     */
    public function status($documentId)
    {
        $status = $this->pandadoc->getDocumentStatus($documentId);
        return response()->json(['status' => $status]);
    }

    public function handleWebhook(Request $request)
    {
        Log::info(json_encode($request->all()));

        return response()->json(['message' => 'Webhook received.']);
    }
}
