<?php

namespace Src\Helpers;

class Payments
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    // Initialize a Paystack transaction (returns authorization_url or throws)
    public function initializePaystack(array $opts): array
    {
        $secret = $this->config['paystack']['secret'] ?? '';
        if (!$secret) throw new \RuntimeException('Paystack secret not configured');

        $payload = json_encode($opts);
        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Authorization: Bearer ' . $secret, 'Content-Type: application/json' ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res === false) throw new \RuntimeException('HTTP error: ' . $err);
        $data = json_decode($res, true);
        return $data;
    }

    public function verifyPaystackReference(string $reference): array
    {
        $secret = $this->config['paystack']['secret'] ?? '';
        if (!$secret) throw new \RuntimeException('Paystack secret not configured');
        $ch = curl_init('https://api.paystack.co/transaction/verify/' . urlencode($reference));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [ 'Authorization: Bearer ' . $secret ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($res, true);
        return $data;
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = $this->config['paystack']['webhook_secret'] ?? '';
        if (!$secret) return false;
        return hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }
}
