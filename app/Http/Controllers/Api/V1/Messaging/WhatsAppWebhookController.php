<?php

namespace App\Http\Controllers\Api\V1\Messaging;

use App\Domain\Messaging\Contracts\MessagingService;
use App\Models\WhatsAppMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Webhook WhatsApp (PRD 7.10; tanpa auth user biasa, idempotent).
 */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request, MessagingService $service)
    {
        $mode = (string) $request->input('hub_mode');
        $token = (string) $request->input('hub_verify_token');
        $challenge = (string) $request->input('hub_challenge');

        if (! $service->verifyWebhook($mode, $token, $challenge)) {
            return response()->json(['message' => 'Invalid verification.'], 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function status(Request $request): JsonResponse
    {
        $payload = $request->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['statuses'] ?? [] as $st) {
                    $providerId = (string) ($st['id'] ?? '');
                    if ($providerId === '') {
                        continue;
                    }

                    $message = WhatsAppMessage::query()
                        ->where('provider_message_id', $providerId)
                        ->first();

                    $message?->applyWebhookStatus(
                        isset($st['status']) ? (string) $st['status'] : null,
                        isset($st['timestamp']) ? (int) $st['timestamp'] : null,
                    );
                }
            }
        }

        return response()->json(null, 204);
    }
}
