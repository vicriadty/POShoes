<?php

namespace App\Domain\Messaging\Adapters;

use App\Domain\Messaging\Contracts\MessagingService;
use App\Domain\Messaging\Exceptions\MessagingSendException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Meta WhatsApp Cloud API adapter (ADR D11).
 */
class MetaWhatsAppCloudService implements MessagingService
{
    public function __construct(
        protected readonly string $accessToken,
        protected readonly string $phoneNumberId,
        protected readonly string $apiVersion = 'v21.0',
        protected readonly ?string $graphUrl = null,
    ) {}

    public function sendTemplate(
        string $toPhone,
        string $templateName,
        string $languageCode,
        array $parameters = [],
        ?string $apiVersion = null,
    ): array {
        $response = $this->client()
            ->post($this->messagesUrl($apiVersion), [
                'messaging_product' => 'whatsapp',
                'to' => $toPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $languageCode],
                    'components' => $this->buildComponents($parameters),
                ],
            ]);

        if ($response->failed()) {
            throw $this->toException($response);
        }

        $json = $response->json();
        $messageId = $json['messages'][0]['id'] ?? null;

        if ($messageId === null) {
            throw new MessagingSendException(
                'Provider tidak mengembalikan message id.',
                502,
                retryable: true,
            );
        }

        return ['provider_message_id' => $messageId];
    }

    public function verifyWebhook(string $hubMode, string $hubToken, string $hubChallenge): bool
    {
        $expected = config('messaging.webhook.verify_token');
        if ($hubMode !== 'subscribe' || $hubToken === '' || $expected === null) {
            return false;
        }

        return hash_equals((string) $expected, $hubToken);
    }

    protected function messagesUrl(?string $apiVersion): string
    {
        $base = $this->graphUrl ?? 'https://graph.facebook.com';
        $version = $apiVersion ?? $this->apiVersion;

        return rtrim($base, '/').'/'.trim($version, '/').'/'.$this->phoneNumberId.'/messages';
    }

    protected function client(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->accessToken)
            ->timeout(20);
    }

    /**
     * @param  array<int|string, mixed>  $parameters
     * @return array<int, array<string, mixed>>
     */
    protected function buildComponents(array $parameters): array
    {
        if ($parameters === []) {
            return [];
        }

        $bodyParams = [];
        foreach ($parameters as $key => $value) {
            $name = is_string($key) ? $key : null;
            $bodyParams[] = $name !== null
                ? ['type' => 'text', 'parameter_name' => $name, 'text' => (string) $value]
                : ['type' => 'text', 'text' => (string) $value];
        }

        return [['type' => 'body', 'parameters' => $bodyParams]];
    }

    protected function toException(Response $response): MessagingSendException
    {
        $json = $response->json();
        $error = $json['error'] ?? [];
        $code = isset($error['code']) ? (string) $error['code'] : (string) $response->status();
        $message = $error['message'] ?? 'WhatsApp API error '.$response->status();
        $retryable = $response->status() === 429 || $response->status() >= 500;

        return new MessagingSendException(
            (string) $message,
            $response->status(),
            $code,
            $retryable,
        );
    }
}
