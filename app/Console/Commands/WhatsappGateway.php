<?php

namespace App\Console\Commands;

use App\Services\WaService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WhatsappGateway extends Command
{
    protected $signature = 'whatsapp:gateway {action=status : status, create, or connect}';

    protected $description = 'Check the configured sender or save its Evolution API pairing QR privately';

    public function handle(): int
    {
        $action = $this->argument('action');
        if (! in_array($action, ['status', 'create', 'connect'], true)) {
            $this->error('Action must be status, create, or connect.');

            return self::FAILURE;
        }
        if (! WaService::isConfigured()) {
            $this->error('Configure WA_SENDER_NUMBER and the gateway credentials in .env first.');

            return self::FAILURE;
        }
        if ($action === 'status') {
            $status = WaService::senderStatus(fresh: true);
            $this->line('WhatsApp sender: '.$status);

            return $status === 'ready' ? self::SUCCESS : self::FAILURE;
        }
        if (config('whatsapp.provider') !== 'evolution') {
            $this->error('Create/connect is supported for Evolution API; pair other providers in their dashboard.');

            return self::FAILURE;
        }
        $client = Http::acceptJson()->withHeaders(['apikey' => config('whatsapp.evolution.key')])
            ->connectTimeout(5)->timeout(15);
        $url = rtrim(config('whatsapp.evolution.url'), '/');
        $instance = config('whatsapp.evolution.instance');
        try {
            $response = $action === 'create'
                ? $client->post($url.'/instance/create', [
                    'instanceName' => $instance, 'integration' => 'WHATSAPP-BAILEYS',
                    'number' => WaService::formatPhoneNumber(config('whatsapp.sender_number')), 'qrcode' => true,
                ])
                : $client->get($url.'/instance/connect/'.rawurlencode($instance));
        } catch (ConnectionException) {
            $this->error('Gateway is unreachable. No QR was saved; check gateway status before trying again.');

            return self::FAILURE;
        }
        if (! $response->successful()) {
            $this->error('Gateway HTTP '.$response->status().'. If the instance already exists, use connect.');

            return self::FAILURE;
        }
        $base64 = $response->json('qrcode.base64') ?? $response->json('base64');
        $image = is_string($base64) ? base64_decode(preg_replace('/^data:image\/png;base64,/', '', $base64), true) : false;
        if (! $image || ! str_starts_with($image, "\x89PNG\r\n\x1a\n")) {
            $this->error('No valid PNG QR returned. Run status; if not connected, try connect to request a new QR.');

            return self::FAILURE;
        }
        $path = storage_path('app/private/whatsapp-pairing.png');
        if (file_put_contents($path, $image) === false) {
            $this->error('Could not save the QR image to private storage.');

            return self::FAILURE;
        }
        chmod($path, 0600);
        $this->info('Scan the QR with the configured sender account: '.$path);
        $this->info('Then run whatsapp:gateway status before enabling reminders.');

        return self::SUCCESS;
    }
}
