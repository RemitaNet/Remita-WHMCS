<?php

declare(strict_types=1);

use PaymentEngine\Whmcs\Support\CallbackUrl;
use PaymentEngine\Whmcs\Support\WhmcsBootstrap;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/../../src/Support/WhmcsBootstrap.php';

WhmcsBootstrap::registerAutoload();

function paymentengine_MetaData(): array
{
    return [
        'DisplayName' => 'Payment Engine',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

function paymentengine_config(): array
{
    $callbackUrl = CallbackUrl::forGatewayModule();

    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Payment Engine',
        ],
        'base_url' => [
            'FriendlyName' => 'API Base URL',
            'Type' => 'text',
            'Size' => '60',
            'Default' => 'https://api-checkout-qa.systemspecsng.com',
            'Description' => 'Payment Engine API base URL for this environment.',
        ],
        'secret_key' => [
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '60',
            'Description' => 'Merchant secret key issued by Payment Engine.',
        ],
        'callback_url' => [
            'FriendlyName' => 'Callback URL',
            'Type' => 'text',
            'Size' => '90',
            'Default' => $callbackUrl,
            'Description' => 'Configure this URL on the Payment Engine merchant profile for browser returns and webhook delivery.',
        ],
    ];
}

function paymentengine_link(array $params): string
{
    $gatewayModuleUrl = rtrim((string) $params['systemurl'], '/') . '/modules/gateways/paymentengine/redirect.php';

    $fields = [
        'invoiceid' => (int) $params['invoiceid'],
        'amount' => (string) $params['amount'],
        'currency' => (string) $params['currency'],
        'description' => (string) $params['description'],
        'firstname' => (string) ($params['clientdetails']['firstname'] ?? ''),
        'lastname' => (string) ($params['clientdetails']['lastname'] ?? ''),
        'email' => (string) ($params['clientdetails']['email'] ?? ''),
        'phonenumber' => (string) ($params['clientdetails']['phonenumber'] ?? ''),
    ];

    $html = '<form method="post" action="' . htmlspecialchars($gatewayModuleUrl, ENT_QUOTES, 'UTF-8') . '">';

    foreach ($fields as $name => $value) {
        $html .= sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
        );
    }

    $html .= '<button type="submit" class="btn btn-primary btn-lg">Pay with Payment Engine</button>';
    $html .= '</form>';

    return $html;
}
