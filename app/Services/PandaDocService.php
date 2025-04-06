<?php

namespace App\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use PandaDoc\Client\Api\DocumentsApi;
use PandaDoc\Client\ApiException;
use PandaDoc\Client\Configuration;
use PandaDoc\Client\Model\DocumentCreateLinkRequest;
use PandaDoc\Client\Model\DocumentCreateRequest;
use PandaDoc\Client\Model\DocumentCreateRequestRecipients;
use PandaDoc\Client\Model\DocumentCreateResponse;
use PandaDoc\Client\Model\DocumentSendRequest;
use PandaDoc\Client\Model\DocumentSendResponse;
use PandaDoc\Client\Model\RecipientRedirect;

class PandaDocService
{
    protected DocumentsApi $documentsApi;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('Authorization', config('services.pandadoc.key'))
            ->setApiKeyPrefix('Authorization', 'API-Key');

        $this->documentsApi = new DocumentsApi(null, $config);
    }

    /**
     * @throws Exception
     */
    public function createDocumentFromTemplate($templateId, $recipientData, object $metadata): DocumentCreateResponse
    {
        $recipient = (new DocumentCreateRequestRecipients())
            ->setEmail($recipientData['EMAIL_ADDRESS']['value'])
            ->setRole('Client')
            ->setRedirect((new RecipientRedirect())
                ->setIsEnabled(true)
                ->setUrl('https://dev.playcreateeat.ae/success')
            );

        $documentRequest = (new DocumentCreateRequest())
            ->setName('Generated Document')
            ->setTemplateUuid($templateId)
            ->setRecipients([$recipient])
            ->setFields($recipientData)
            ->setParseFormFields(true)
            ->setMetadata($metadata);

        try {
            return $this->documentsApi->createDocument($documentRequest);
        } catch (Exception $e) {
            throw new Exception("Document creation failed: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function sendDocument($documentId): DocumentSendResponse
    {
        $sendRequest = (new DocumentSendRequest())
            ->setSilent(true)
            ->setSender(['email' => 'tech@playcreateeat.ae'])
            ->setSubject('Please sign this document')
            ->setMessage('Review and sign this document.');

        try {
            return $this->documentsApi->sendDocument($documentId, $sendRequest);
        } catch (Exception $e) {
            throw new Exception("Failed to send document: " . $e->getMessage());
        }
    }

    /**
     * @throws ApiException
     */
    public function checkDocumentStatus($documentId): bool
    {
        $maxRetries = 5;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            sleep(2);

            $documentStatus = $this->documentsApi->statusDocument($documentId);
            if ($documentStatus->getStatus() === 'document.draft') {
                return true;
            }

            $retryCount++;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function getDocumentStatus($documentId): string
    {
        try {
            $status = $this->documentsApi->statusDocument($documentId);
            return $status->getStatus();

        } catch (Exception $e) {
            throw new Exception("Failed to get document status: " . $e->getMessage());
        }
    }

    /**
     * @throws ApiException
     * @throws Exception
     */
    public function generateDocumentLink($documentId): JsonResponse
    {
        $linkRequest = new DocumentCreateLinkRequest();
        $linkRequest->setRecipient('tech@playcreateeat.ae');

        try {
            $response = $this->documentsApi->createDocumentLink($documentId, $linkRequest);

            return response()->json([
                'message'      => 'Document is ready for signing.',
                'signing_link' => "https://app.pandadoc.com/s/{$response['id']}"
            ]);
        } catch (Exception $e) {
            throw new Exception("Failed to generate public link: " . $e->getMessage());
        }
    }
}
