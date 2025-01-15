<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\PandaDocService;
use Exception;
use Log;
use Request;

class PandaDocController extends Controller
{
    public function __construct(protected PandaDocService $pandadoc)
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/documents/create",
     *     summary="Create and send a document using PandaDoc",
     *     tags={"Documents"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"template_id", "recipient_data", "kids_array"},
     *             @OA\Property(property="template_id", type="string", example="WVGshXQkryavX2rVoCwQf5", description="PandaDoc template ID"),
     *             @OA\Property(
     *                 property="recipient_data",
     *                 type="object",
     *                 required={"first_name", "last_name", "phone_number", "email_address", "passport_number"},
     *                 @OA\Property(property="first_name", type="string", example="John", description="Recipient's first name"),
     *                 @OA\Property(property="last_name", type="string", example="Doe", description="Recipient's last name"),
     *                 @OA\Property(property="phone_number", type="string", example="+971501234567", description="Recipient's phone number"),
     *                 @OA\Property(property="email_address", type="string", format="email", example="john.doe@example.com", description="Recipient's email address"),
     *                 @OA\Property(property="passport_number", type="string", example="P1234567", description="Recipient's passport number")
     *             ),
     *             @OA\Property(
     *                 property="kids_array",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"name", "age"},
     *                     @OA\Property(property="name", type="string", example="Jane Doe", description="Child's name"),
     *                     @OA\Property(property="age", type="integer", example=10, description="Child's age")
     *                 ),
     *                 description="Array of children information"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document created and sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Document created and sent successfully!"),
     *             @OA\Property(
     *                 property="document",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", example="abc123", description="ID of the created document"),
     *                 @OA\Property(property="status", type="string", example="sent", description="Status of the document"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-10T12:34:56Z", description="Document creation timestamp")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Document creation failed or server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Document creation failed.")
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
                    $signingLink = $this->pandadoc->generateDocumentLink($document->getId());

                    return response()->json([
                        'message'      => 'Document is ready for signing.',
                        'signing_link' => $signingLink
                    ]);
                }

                return response()->json(['error' => 'Document is not ready for signing.'], 500);
            }
            return response()->json(['error' => 'Document creation failed.'], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        Log::info(json_encode($request->all()));

        return response()->json(['message' => 'Webhook received.']);
    }
}
