<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaService
{
    /**
     * Kirim pesan WhatsApp melalui Evolution API (atau Provider Gateway pilihan).
     *
     * @param string $target Nomor HP penerima (e.g. 081234567890 / 6281234567890)
     * @param string $message Teks pesan yang akan dikirim
     * @return bool Status berhasil/gagal
     */
    public static function sendMessage(string $target, string $message): bool
    {
        $formattedPhone = self::formatPhoneNumber($target);
        
        $provider = env('WA_PROVIDER', 'evolution'); // Default: 'evolution'

        if ($provider === 'evolution') {
            return self::sendViaEvolutionApi($formattedPhone, $message);
        }

        return self::sendViaFonnte($formattedPhone, $message);
    }

    /**
     * Pengiriman via Evolution API (v1 / v2)
     * Repo: https://github.com/evolution-foundation/evolution-api
     * Endpoint: POST /message/sendText/{instance}
     */
    protected static function sendViaEvolutionApi(string $phone, string $message): bool
    {
        $baseUrl = rtrim(env('EVOLUTION_API_URL', 'http://evolution-api:8080'), '/');
        $apiKey = env('EVOLUTION_API_KEY', '429683C4C977415CAAFCCE10F7D57E11');
        $instance = env('EVOLUTION_INSTANCE', 'credit-hp');

        $endpoint = "{$baseUrl}/message/sendText/{$instance}";

        try {
            $response = Http::withHeaders([
                'apikey'       => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(15)->post($endpoint, [
                'number'  => $phone,
                'text'    => $message,
                'options' => [
                    'delay'    => 1200,
                    'presence' => 'composing',
                ],
            ]);

            if ($response->successful()) {
                Log::info("WA Evolution API Success to {$phone}: " . $response->body());
                return true;
            }

            Log::error("WA Evolution API Failed ({$response->status()}) to {$phone}: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error("WA Evolution API Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fallback pengiriman via Fonnte API
     */
    protected static function sendViaFonnte(string $phone, string $message): bool
    {
        $token = env('WA_GATEWAY_TOKEN', '');
        $endpoint = env('WA_GATEWAY_URL', 'https://api.fonnte.com/send');

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(15)->post($endpoint, [
                'target'  => $phone,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("WA Fonnte Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalisasi nomor HP ke format internasional 62...
     */
    public static function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }
}