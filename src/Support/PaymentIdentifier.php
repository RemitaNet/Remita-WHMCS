<?php

declare(strict_types=1);

namespace PaymentEngine\Whmcs\Support;

final class PaymentIdentifier
{
    public static function build(int $invoiceId): string
    {
        return sprintf('whmcs-%d-%d-%06d', $invoiceId, time(), random_int(100000, 999999));
    }

    public static function extractInvoiceId(string $paymentIdentifier): ?int
    {
        if (preg_match('/^whmcs-(\d+)-\d+-\d{6}$/', $paymentIdentifier, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
}
