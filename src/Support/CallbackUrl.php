<?php

declare(strict_types=1);

namespace PaymentEngine\Whmcs\Support;

final class CallbackUrl
{
    public static function forGatewayModule(): string
    {
        return rtrim(self::systemUrl(), '/') . '/modules/gateways/callback/paymentengine.php';
    }

    public static function invoiceUrl(int $invoiceId, string $status): string
    {
        return sprintf(
            '%s/viewinvoice.php?id=%d&payment_engine_result=%s',
            rtrim(self::systemUrl(), '/'),
            $invoiceId,
            rawurlencode($status)
        );
    }

    private static function systemUrl(): string
    {
        if (isset($GLOBALS['CONFIG']['SystemURL']) && is_string($GLOBALS['CONFIG']['SystemURL']) && $GLOBALS['CONFIG']['SystemURL'] !== '') {
            return $GLOBALS['CONFIG']['SystemURL'];
        }

        if (function_exists('App')) {
            try {
                $url = \App::getSystemURL();

                if (is_string($url) && $url !== '') {
                    return $url;
                }
            } catch (\Throwable) {
            }
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }
}
