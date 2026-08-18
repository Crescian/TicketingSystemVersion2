<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

class InternalApiTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $serviceUrl,
        private readonly string $serviceKey,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (!$email instanceof Email) {
            throw new TransportException('internal-api transport only supports Symfony Email messages.');
        }

        $envelope = $message->getEnvelope();
        $to = collect($envelope->getRecipients())
            ->map(fn ($address) => $address->getAddress())
            ->implode(',');

        $headers = $email->getHeaders();

        $response = Http::withHeaders(['X-Internal-Key' => $this->serviceKey])
            ->timeout(10)
            ->post(rtrim($this->serviceUrl, '/') . '/send', array_filter([
                'to' => $to,
                'from' => $envelope->getSender()->toString(),
                'subject' => (string) $email->getSubject(),
                'html' => $email->getHtmlBody(),
                'text' => $email->getTextBody(),
                'message_id' => $headers->get('Message-ID')?->getBodyAsString(),
                'references' => $headers->get('References')?->getBodyAsString(),
                'in_reply_to' => $headers->get('In-Reply-To')?->getBodyAsString(),
            ]));

        if ($response->failed()) {
            throw new TransportException('internal-api mail transport failed: ' . $response->body());
        }
    }

    public function __toString(): string
    {
        return 'internal-api';
    }
}
