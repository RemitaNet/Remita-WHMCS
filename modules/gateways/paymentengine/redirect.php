<?php

declare(strict_types=1);

use PaymentEngine\Sdk\Client\PaymentEngineClient;
use PaymentEngine\Whmcs\Support\CallbackUrl;
use PaymentEngine\Whmcs\Support\ChargePayloadBuilder;
use PaymentEngine\Whmcs\Support\PaymentIdentifier;
use PaymentEngine\Whmcs\Support\WhmcsBootstrap;

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
require_once __DIR__ . '/../../../src/Support/WhmcsBootstrap.php';

WhmcsBootstrap::registerAutoload();

$gatewayModuleName = 'paymentengine';
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (empty($gatewayParams['type'])) {
    http_response_code(500);
    exit('Payment Engine module is not activated.');
}

$invoiceId = isset($_POST['invoiceid']) ? (int) $_POST['invoiceid'] : 0;
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayModuleName);

$phoneNumber = trim((string) ($_POST['phonenumber'] ?? ''));

if ($phoneNumber === '') {
    logTransaction($gatewayModuleName, $_POST, 'Failed: Missing phone number');
    header('Location: ' . CallbackUrl::invoiceUrl($invoiceId, 'missing_phone'));
    exit;
}

try {
    $client = new PaymentEngineClient(
        (string) ($gatewayParams['base_url'] ?? ''),
        (string) ($gatewayParams['secret_key'] ?? '')
    );

    $paymentIdentifier = PaymentIdentifier::build($invoiceId);
    $returnUrl = CallbackUrl::forGatewayModule();
    $payload = ChargePayloadBuilder::fromRequest($_POST, $paymentIdentifier, $returnUrl);
    $response = $client->redirectCheckout->initiate($payload);
    $paymentLink = (string) ($response['data']['paymentLink'] ?? '');

    if ($paymentLink === '' || filter_var($paymentLink, FILTER_VALIDATE_URL) === false) {
        throw new RuntimeException('Payment Engine returned an invalid payment link.');
    }

    logTransaction($gatewayModuleName, [
        'request' => $payload,
        'response' => $response,
    ], 'RedirectInitiated');

    header('Location: ' . $paymentLink);
    exit;
} catch (Throwable $throwable) {
    logTransaction($gatewayModuleName, [
        'request' => $_POST,
        'error' => $throwable->getMessage(),
    ], 'Failed');

    header('Location: ' . CallbackUrl::invoiceUrl($invoiceId, 'initiation_failed'));
    exit;
}
