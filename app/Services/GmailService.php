<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Gmail as GoogleServiceGmail;
use Google\Service\Gmail\Message as GoogleServiceGmailMessage;

/**
 * GmailService - odosielanie mailov cez Gmail API (Service Account + Domain Wide Delegation)
 *
 * - Nepoužíva SMTP (takže 454/535 SMTP bloky ťa netrápia)
 * - Impersonácia mailboxu cez setSubject()
 * - Odosiela raw MIME (toString() zo Symfony Emailu / Laravel Mailable)
 */
class GmailService
{
    private GoogleClient $client;
    private ?GoogleServiceGmail $gmail = null;

    private string $credentialsPath;
    private ?string $impersonatedEmail = null;

    public function __construct()
    {
        // Cestu si nastav v .env, aby si nemusel hardcodovať.
        $this->credentialsPath = (string) env('GOOGLE_SERVICE_ACCOUNT_JSON', '');

        if ($this->credentialsPath === '' || !is_file($this->credentialsPath)) {
            throw new \RuntimeException(
                'GmailService: missing or invalid GOOGLE_SERVICE_ACCOUNT_JSON path: ' . $this->credentialsPath
            );
        }

        $this->client = new GoogleClient();
        $this->client->setApplicationName((string) env('APP_NAME', 'Laravel'));
        $this->client->setAuthConfig($this->credentialsPath);

        // Pre send stačí GMAIL_SEND.
        // Ak budeš niekedy potrebovať labely/mark-as-read, pridaj MODIFY.
        $this->client->setScopes([
            GoogleServiceGmail::GMAIL_SEND,
        ]);

        // Bez subjectu je service account "sám za seba" a Gmail API nebude fungovať pre mailbox.
        // Subject nastavíš cez impersonate().
    }

    /**
     * Nastaví mailbox (impersonáciu), z ktorého sa bude odosielať.
     */
    public function impersonate(string $email): self
    {
        $email = trim($email);
        if ($email === '') {
            throw new \InvalidArgumentException('GmailService::impersonate(): empty email');
        }

        // Ak sa zmenil subject, treba "refresh" service wrapperu.
        if ($this->impersonatedEmail !== $email) {
            $this->impersonatedEmail = $email;
            $this->client->setSubject($email);
            $this->gmail = new GoogleServiceGmail($this->client);
        }

        return $this;
    }

    /**
     * Odoslanie raw MIME správy cez Gmail API.
     *
     * @param string      $rawMime  komplet MIME (napr. (new Symfony\Component\Mime\Email())->toString())
     * @param string|null $threadId voliteľné, ak chceš posielať do threadu
     */
    public function sendRaw(string $rawMime, ?string $threadId = null)
    {
        if ($this->gmail === null) {
            // fallback – ak niekto zabudol impersonate()
            $default = (string) env('GMAIL_IMPERSONATE', '');
            if ($default === '') {
                throw new \RuntimeException('GmailService: call impersonate() first or set GMAIL_IMPERSONATE in .env');
            }
            $this->impersonate($default);
        }

        // Gmail API vyžaduje base64url (RFC 4648 §5) bez paddingu '='
        $raw = rtrim(strtr(base64_encode($rawMime), '+/', '-_'), '=');

        $msg = new GoogleServiceGmailMessage();
        $msg->setRaw($raw);

        if (!empty($threadId)) {
            $msg->setThreadId($threadId);
        }

        // "me" = impersonovaný používateľ (subject)
        return $this->gmail->users_messages->send('me', $msg);
    }
}
