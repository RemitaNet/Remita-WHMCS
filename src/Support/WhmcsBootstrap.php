<?php

declare(strict_types=1);

namespace PaymentEngine\Whmcs\Support;

final class WhmcsBootstrap
{
    public static function registerAutoload(): void
    {
        self::requirePhpSdk();

        spl_autoload_register(static function (string $class): void {
            $prefix = 'PaymentEngine\\Whmcs\\';

            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $relative . '.php';

            if (file_exists($path)) {
                require_once $path;
            }
        });
    }

    private static function requirePhpSdk(): void
    {
        $candidates = [
            dirname(__DIR__, 2) . '/vendor/payment-engine-sdk/index.php',
            dirname(__DIR__, 4) . '/developer-tools/server-side-sdks/php/src/index.php',
            dirname(__DIR__, 5) . '/developer-tools/server-side-sdks/php/src/index.php',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                require_once $candidate;
                return;
            }
        }

        throw new \RuntimeException('Payment Engine PHP SDK bootstrap file could not be found for WHMCS.');
    }
}
