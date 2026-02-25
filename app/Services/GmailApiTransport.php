<?php

namespace App\Services;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class GmailApiTransport extends AbstractTransport
{
    private GmailService $gmail;
    private ?string $impersonateEmail;

    public function __construct(
        GmailService $gmail,
        ?string $impersonateEmail = null,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($dispatcher, $logger);

        $this->gmail = $gmail;
        $this->impersonateEmail = $impersonateEmail ? trim($impersonateEmail) : null;
    }

    protected function doSend(SentMessage $sentMessage): void
    {
        // Dôležité: getMessage() je “prepared” verzia (typicky bez Bcc headeru),
        // aby si Bcc príjemcov neprepálil do hlavičiek.
        $mime = $sentMessage->getMessage()->toString();

        $impersonate = $this->impersonateEmail ?: (string) env('GMAIL_IMPERSONATE', '');
        if ($impersonate === '') {
            throw new \RuntimeException('GmailApiTransport: missing impersonate email (set GMAIL_IMPERSONATE or mailer impersonate config)');
        }

        $this->gmail->impersonate($impersonate)->sendRaw($mime, null);
    }

    public function __toString(): string
    {
        return 'gmailapi';
    }
}
