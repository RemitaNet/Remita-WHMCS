<?php

declare(strict_types=1);

namespace PaymentEngine\Whmcs\Support;

final class ChargePayloadBuilder
{
    public static function fromRequest(array $request, string $paymentIdentifier, string $returnUrl): array
    {
        $invoiceId = (int) ($request['invoiceid'] ?? 0);
        $description = trim((string) ($request['description'] ?? ''));

        return [
            'firstName' => trim((string) ($request['firstname'] ?? '')),
            'lastName' => trim((string) ($request['lastname'] ?? '')),
            'email' => trim((string) ($request['email'] ?? '')),
            'phoneNumber' => trim((string) ($request['phonenumber'] ?? '')),
            'paymentIdentifier' => $paymentIdentifier,
            'currency' => trim((string) ($request['currency'] ?? 'NGN')),
            'narration' => $description !== '' ? $description : sprintf('WHMCS invoice #%d', $invoiceId),
            'amount' => AmountNormalizer::toKobo((string) ($request['amount'] ?? '0')),
            'returnUrl' => $returnUrl,
        ];
    }
}
