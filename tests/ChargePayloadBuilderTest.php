<?php

declare(strict_types=1);

use PaymentEngine\Whmcs\Support\ChargePayloadBuilder;

final class ChargePayloadBuilderTest extends TestCase
{
    public function testBuildsChargePayloadWithKoboAmount(): void
    {
        $payload = ChargePayloadBuilder::fromRequest([
            'invoiceid' => 77,
            'firstname' => 'Ada',
            'lastname' => 'Lovelace',
            'email' => 'ada@example.com',
            'phonenumber' => '08030000000',
            'currency' => 'NGN',
            'description' => 'Invoice #77',
            'amount' => '3000.00',
        ], 'whmcs-77-1234567890-654321', 'https://example.com/callback');

        $this->assertSame(300000, $payload['amount']);
        $this->assertSame('08030000000', $payload['phoneNumber']);
        $this->assertSame('https://example.com/callback', $payload['returnUrl']);
    }
}
