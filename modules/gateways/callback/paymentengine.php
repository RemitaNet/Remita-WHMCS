<?php

declare(strict_types=1);

use PaymentEngine\Sdk\Client\PaymentEngineClient;
use PaymentEngine\Whmcs\Support\AmountNormalizer;
use PaymentEngine\Whmcs\Support\CallbackUrl;
use PaymentEngine\Whmcs\Support\PaymentIdentifier;
use PaymentEngine\Whmcs\Support\PaymentStatusMapper;
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

$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($requestMethod === 'POST') {
    $rawPayload = file_get_contents('php://input');
    $payload = json_decode($rawPayload !== false ? $rawPayload : '', true);

    if (!is_array($payload)) {
        http_response_code(400);
        exit('Invalid payload');
    }

    $paymentIdentifier = (string) ($payload['data']['paymentIdentifier'] ?? '');
    $invoiceId = PaymentIdentifier::extractInvoiceId($paymentIdentifier);

    if ($invoiceId === null) {
        logTransaction($gatewayModuleName, $payload, 'Failed: Missing invoice reference');
        http_response_code(400);
        exit('Missing payment identifier');
    }

    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayModuleName);
    $mappedStatus = PaymentStatusMapper::mapWebhookPayload($payload);
    $transactionId = (string) ($payload['data']['transactionId'] ?? $paymentIdentifier);

    if ($mappedStatus === PaymentStatusMapper::STATUS_SUCCESS) {
        checkCbTransID($transactionId);
        addInvoicePayment(
            $invoiceId,
            $transactionId,
            AmountNormalizer::fromKobo($payload['data']['amount'] ?? 0),
            0.0,
            $gatewayModuleName
        );
    }

    logTransaction($gatewayModuleName, $payload, ucfirst($mappedStatus));
    http_response_code(200);
    exit('Webhook processed');
}

$paymentIdentifier = isset($_GET['paymentIdentifier'])
    ? trim((string) $_GET['paymentIdentifier'])
    : '';

$invoiceId = PaymentIdentifier::extractInvoiceId($paymentIdentifier);

if ($invoiceId === null) {
    http_response_code(400);
    exit('Missing payment identifier');
}

$invoiceId = checkCbInvoiceID($invoiceId, $gatewayModuleName);

try {
    $client = new PaymentEngineClient(
        (string) ($gatewayParams['base_url'] ?? ''),
        (string) ($gatewayParams['secret_key'] ?? '')
    );

    $response = $client->payments->query($paymentIdentifier);
    $mappedStatus = PaymentStatusMapper::mapQueryResponse($response);

    if ($mappedStatus === PaymentStatusMapper::STATUS_SUCCESS) {
        checkCbTransID($paymentIdentifier);
        addInvoicePayment(
            $invoiceId,
            $paymentIdentifier,
            AmountNormalizer::fromKobo($response['data']['amount'] ?? 0),
            0.0,
            $gatewayModuleName
        );
    }

    logTransaction($gatewayModuleName, $response, ucfirst($mappedStatus));

    header('Location: ' . CallbackUrl::invoiceUrl($invoiceId, $mappedStatus));
    exit;
} catch (Throwable $throwable) {
    logTransaction($gatewayModuleName, [
        'paymentIdentifier' => $paymentIdentifier,
        'error' => $throwable->getMessage(),
    ], 'Failed');

    header('Location: ' . CallbackUrl::invoiceUrl($invoiceId, 'verification_failed'));
    exit;
}
