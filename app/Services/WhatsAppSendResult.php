<?php

namespace App\Services;

class WhatsAppSendResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $errorCode = null,
        public readonly bool $retryable = false,
        public readonly ?string $messageId = null,
    ) {}
}
