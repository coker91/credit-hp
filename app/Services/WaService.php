<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaService
{
    public static function sendMessage(string $target, string $message): bool
    {
        return self::send($target, $message)->status === 'sent';
    }

    public static function isConfigured(): bool
    {
        if (! preg_match('/^[1-9][0-9]{7,14}$/', self::formatPhoneNumber((string) config('whatsapp.sender_number')))) {
            return false;
        }

        return match (config('whatsapp.provider')) {
            'evolution' => filled(config('whatsapp.evolution.url'))
                && filled(config('whatsapp.evolution.key')) && filled(config('whatsapp.evolution.instance')),
            'fonnte' => filled(config('whatsapp.fonnte.url')) && filled(config('whatsapp.fonnte.token')),
            default => false,
        };
    }

    public static function senderStatus(bool $fresh = false): string
    {
        if (! self::isConfigured()) {
            return 'gateway_not_configured';
        }
        if (config('whatsapp.provider') !== 'evolution') {
            return 'ready'; // Fonnte's token is bound to the sender in its dashboard.
        }
        $cacheKey = 'wa-sender:'.hash('sha256', json_encode(config('whatsapp.evolution')).config('whatsapp.sender_number'));
        if (! $fresh && Cache::get($cacheKey) === 'ready') {
            return 'ready';
        }
        try {
            $response = Http::acceptJson()->withHeaders(['apikey' => config('whatsapp.evolution.key')])
                ->connectTimeout(5)->timeout(15)
                ->get(rtrim(config('whatsapp.evolution.url'), '/').'/instance/fetchInstances', [
                    'instanceName' => config('whatsapp.evolution.instance'),
                ]);
        } catch (ConnectionException) {
            return 'gateway_unreachable';
        }
        if (! $response->successful() || ! is_array($response->json())) {
            return 'gateway_status_unavailable';
        }
        $instance = collect($response->json())->first(fn ($item) => is_array($item)
            && ($item['name'] ?? data_get($item, 'instance.instanceName')) === config('whatsapp.evolution.instance'));
        if (! $instance) {
            return 'instance_not_found';
        }
        $owner = $instance['ownerJid'] ?? data_get($instance, 'instance.owner');
        $number = explode('@', (string) $owner)[0];
        if (self::formatPhoneNumber($number) !== self::formatPhoneNumber((string) config('whatsapp.sender_number'))) {
            return 'sender_number_mismatch';
        }
        if (($instance['connectionStatus'] ?? data_get($instance, 'instance.status')) !== 'open') {
            return 'sender_not_connected';
        }
        Cache::put($cacheKey, 'ready', 60);

        return 'ready';
    }

    public static function send(string $target, string $message): WhatsAppSendResult
    {
        $phone = self::formatPhoneNumber($target);
        if (! preg_match('/^[1-9][0-9]{7,14}$/', $phone)) {
            return new WhatsAppSendResult('failed', 'invalid_phone');
        }
        if (! self::isConfigured()) {
            return new WhatsAppSendResult('failed', 'gateway_not_configured');
        }

        $senderStatus = self::senderStatus();
        if ($senderStatus !== 'ready') {
            return new WhatsAppSendResult('failed', $senderStatus, true);
        }

        $evolution = config('whatsapp.provider') === 'evolution';
        $url = $evolution
            ? rtrim(config('whatsapp.evolution.url'), '/').'/message/sendText/'.rawurlencode(config('whatsapp.evolution.instance'))
            : config('whatsapp.fonnte.url');
        $headers = $evolution
            ? ['apikey' => config('whatsapp.evolution.key')]
            : ['Authorization' => config('whatsapp.fonnte.token')];
        $payload = $evolution
            ? ['number' => $phone, 'text' => $message]
            : ['target' => $phone, 'message' => $message];

        try {
            // Do not retry HTTP requests here: a lost response may already have sent a message.
            $response = Http::acceptJson()->withHeaders($headers)
                ->connectTimeout(5)->timeout(15)->post($url, $payload);
        } catch (ConnectionException) {
            Log::warning('WhatsApp gateway response unavailable; delivery needs review.');

            return new WhatsAppSendResult('unknown', 'connection_uncertain');
        }

        if ($response->status() === 429) {
            return new WhatsAppSendResult('failed', 'rate_limited', true);
        }
        if ($response->serverError()) {
            return new WhatsAppSendResult('unknown', 'gateway_server_error');
        }
        if (! $response->successful()) {
            return new WhatsAppSendResult('failed', 'gateway_http_'.$response->status());
        }
        if (! $evolution && $response->json('status') === false) {
            return new WhatsAppSendResult('failed', 'gateway_rejected');
        }

        $messageId = $evolution ? $response->json('key.id') : $response->json('id.0');
        $accepted = $evolution ? (is_scalar($messageId) && filled($messageId)) : $response->json('status') === true;
        if (! $accepted) {
            return new WhatsAppSendResult('unknown', 'unexpected_response');
        }

        // Accepted by gateway; handset delivery/read receipts are not implied.
        return new WhatsAppSendResult('sent', messageId: is_scalar($messageId) ? (string) $messageId : null);
    }

    public static function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        // Indonesian mobile numbers are often entered without their leading zero.
        return str_starts_with($phone, '8') ? '62'.$phone : $phone;
    }
}
